# Implementation Notes

This document provides an overview of how the multi-system capability was implemented in this Filament plugin.

## What Was Already Implemented (PR #1)

The core multi-system architecture was already implemented in the merged PR #1. This included:

### Core Architecture
- **Config System** (`config/failed-jobs.php`) - Configuration for multiple projects with database connections
- **Action Spool Pattern** - Instead of executing artisan commands directly, actions are written to a spool table
- **FailedJobAction Model** - Eloquent model for the action spool table
- **Migration** - Database migration for the `failed_job_action_spool` table
- **ProjectRegistry** - Service to manage and retrieve project configurations
- **FailedJobAggregator** - Aggregates failed jobs from multiple database connections
- **FailedJobActionDispatcher** - Writes actions to the spool instead of executing them
- **ManagesJobs Trait** - Groups jobs by project and dispatches to spool
- **Filament Table** - Custom data source (not Eloquent-bound) that shows jobs from all projects
- **Project Column** - UI displays which project each job belongs to
- **Project Filter** - Allows filtering by project in the Filament UI

### Key Design Decisions
1. **No Direct Execution** - Actions are never executed directly on remote systems
2. **Database-Driven** - Uses database connections to access remote failed_jobs tables
3. **Spool Pattern** - Actions queue in a spool table for remote processing
4. **Backward Compatible** - Falls back to single-project mode when no projects configured
5. **Horizon Aware** - Each project can specify if it uses Laravel Horizon

## What We Added

To complete the implementation and make it production-ready, we added:

### 1. Console Command (`src/Commands/ProcessActionSpoolCommand.php`)
A command that remote projects can run via cron to process pending actions from the spool:

**Features:**
- `--project` option to filter by project key
- `--limit` option to control batch size
- Processes retry, delete, retry-queue, and prune actions
- Updates action status (pending → processing → completed/failed)
- Comprehensive error handling and logging
- Works with both Horizon and non-Horizon projects

**Logic Flow:**
```
1. Query pending actions for project
2. Mark action as processing
3. Execute the action locally (queue:retry, queue:forget, etc.)
4. Update action status to completed
5. Handle errors and mark as failed if needed
```

### 2. Service Provider Registration
Updated `FailedJobsServiceProvider.php` to register the command:
- Added command to package configuration
- Now available as `php artisan failed-jobs:process-spool`

### 3. Comprehensive Documentation

#### README.md Updates
- Added "Multi-Project Setup" section with overview
- Configuration examples for database connections
- Remote project setup instructions
- Action spool command documentation
- Security considerations

#### MULTI_PROJECT_SETUP.md
Created detailed step-by-step guide (200+ lines) covering:
- Prerequisites and overview
- Central dashboard setup
- Database connection configuration
- Remote project setup
- Permission setup with SQL examples
- Testing procedures
- Monitoring and troubleshooting
- Best practices for production
- Advanced configuration scenarios
- Security hardening

### 4. Setup Helper Files

#### `stubs/kernel-schedule.php.stub`
Ready-to-use cron schedule template for remote projects:
```php
$schedule->command('failed-jobs:process-spool --project=your-project-key')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
```

#### `stubs/env-example.stub`
Environment variable examples for database connections to remote projects.

### 5. Enhanced Configuration

Updated `config/failed-jobs.php` with:
- Detailed inline comments explaining each option
- Multiple real-world examples
- Clear indication of backward compatibility
- Examples for Horizon and non-Horizon projects

## Technical Architecture

### Data Flow

```
┌──────────────────────────────────────────────────────────────┐
│                   Central Dashboard                          │
│                                                              │
│  1. User clicks "Retry" on failed job                       │
│     ↓                                                        │
│  2. Action written to failed_job_action_spool table         │
│     ↓                                                        │
│  3. Status: pending                                         │
└──────────────────────────────────────────────────────────────┘
                            ↓
              ┌─────────────┴──────────────┐
              ↓                            ↓
┌─────────────────────────┐   ┌─────────────────────────┐
│   Remote Project A      │   │   Remote Project B      │
│                         │   │                         │
│  Cron: * * * * *        │   │  Cron: * * * * *        │
│     ↓                   │   │     ↓                   │
│  process-spool          │   │  process-spool          │
│  --project=A            │   │  --project=B            │
│     ↓                   │   │     ↓                   │
│  4. Read pending        │   │  4. Read pending        │
│     actions for A       │   │     actions for B       │
│     ↓                   │   │     ↓                   │
│  5. Execute locally     │   │  5. Execute locally     │
│     queue:retry {id}    │   │     queue:retry {id}    │
│     ↓                   │   │     ↓                   │
│  6. Update status:      │   │  6. Update status:      │
│     completed           │   │     completed           │
└─────────────────────────┘   └─────────────────────────┘
```

### Database Schema

#### failed_job_action_spool
```
id              BIGINT (PK)
project         VARCHAR     - Project key from config
action          VARCHAR     - retry-jobs, delete-jobs, retry-queue, prune
payload         JSON        - Action-specific data (job IDs, hours, etc.)
status          VARCHAR     - pending, processing, completed, failed
attempts        INT         - Number of processing attempts
available_at    TIMESTAMP   - When action should be processed
processed_at    TIMESTAMP   - When action was completed
error           TEXT        - Error message if failed
created_at      TIMESTAMP
updated_at      TIMESTAMP

Indexes:
- (project, status) - Fast lookup of pending actions per project
- available_at      - Delayed action processing
```

## Why This Architecture?

### Problem
- Need to manage failed jobs across multiple Laravel projects
- Cannot execute artisan commands remotely
- Each project has its own queue configuration
- Some projects use Horizon, others don't

### Solution
- **Action Spool Pattern**: Central dashboard writes intent, remote systems execute
- **Database Connections**: Read failed jobs from all project databases
- **Cron-Based Processing**: Each remote project processes its own actions
- **Project-Scoped**: Actions are filtered by project key for security

### Benefits
1. **No Remote Execution** - No need for SSH or artisan remote execution
2. **Project Autonomy** - Each project executes actions in its own context
3. **Resilient** - Network issues don't break the system
4. **Auditable** - Full history of all actions in the spool
5. **Scalable** - Supports unlimited number of projects
6. **Secure** - Database permissions control access
7. **Flexible** - Works with any queue driver and configuration

## Testing Checklist

When deploying, verify:

- [ ] Central dashboard can connect to all remote databases
- [ ] Migration creates `failed_job_action_spool` table
- [ ] Filament table displays jobs from all projects
- [ ] Project filter works correctly
- [ ] Retry action creates spool entry
- [ ] Delete action creates spool entry
- [ ] Remote cron job runs every minute
- [ ] Actions process successfully on remote systems
- [ ] Action status updates to "completed"
- [ ] Errors are logged in spool table
- [ ] Database permissions are correct

## Future Enhancements

Potential improvements for future versions:

1. **Real-time Updates** - Use WebSockets to show action status updates
2. **Action History** - UI to view completed actions
3. **Failed Action Retry** - Auto-retry failed actions
4. **Metrics Dashboard** - Statistics on action processing
5. **Alert System** - Notifications when actions fail
6. **Bulk Action Optimization** - Process multiple jobs in single artisan call
7. **Action Prioritization** - Priority queue for urgent retries
8. **Multi-Tenancy Support** - Support for tenant-specific configurations

## Support

For questions or issues:
- Review MULTI_PROJECT_SETUP.md for detailed setup instructions
- Check README.md for quick start guide
- Open GitHub issues for bug reports or feature requests
