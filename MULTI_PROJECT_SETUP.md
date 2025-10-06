# Multi-Project Setup Guide

This guide provides step-by-step instructions for setting up the Failed Jobs plugin to monitor and manage failed jobs across multiple Laravel projects.

## Overview

The plugin uses an **action spool pattern** to enable cross-project failed job management:

1. **Central Dashboard** - Displays failed jobs from all projects
2. **Action Spool** - Queue of actions (retry/delete) to be executed by remote projects
3. **Remote Workers** - Cron jobs that process actions for their respective projects

## Prerequisites

- A central Laravel application with Filament installed (the "dashboard")
- One or more remote Laravel applications with queue workers
- Database connectivity from the dashboard to each remote project's database
- Redis queue driver on all remote projects (recommended)

## Step 1: Install on Central Dashboard

```bash
composer require srinathreddydudi/failed-jobs
```

Register the plugin in your Filament panel provider:

```php
use SrinathReddyDudi\FailedJobs\FailedJobsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FailedJobsPlugin::make());
}
```

## Step 2: Configure Database Connections

### Central Dashboard - `config/database.php`

Add connections for each remote project:

```php
'connections' => [
    'mysql' => [
        // Your main connection
    ],
    
    // Remote Project API
    'mysql_api' => [
        'driver' => 'mysql',
        'host' => env('DB_API_HOST'),
        'database' => env('DB_API_DATABASE'),
        'username' => env('DB_API_USERNAME'),
        'password' => env('DB_API_PASSWORD'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ],
    
    // Remote Project Worker
    'mysql_worker' => [
        'driver' => 'mysql',
        'host' => env('DB_WORKER_HOST'),
        'database' => env('DB_WORKER_DATABASE'),
        'username' => env('DB_WORKER_USERNAME'),
        'password' => env('DB_WORKER_PASSWORD'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ],
],
```

### Central Dashboard - `.env`

```env
# Remote Project API
DB_API_HOST=api-server.example.com
DB_API_PORT=3306
DB_API_DATABASE=api_production
DB_API_USERNAME=dashboard_readonly
DB_API_PASSWORD=secure_password

# Remote Project Worker
DB_WORKER_HOST=worker-server.example.com
DB_WORKER_PORT=3306
DB_WORKER_DATABASE=worker_production
DB_WORKER_USERNAME=dashboard_readonly
DB_WORKER_PASSWORD=secure_password
```

## Step 3: Configure Projects

### Central Dashboard - `config/failed-jobs.php`

Publish and edit the config:

```bash
php artisan vendor:publish --tag="failed-jobs-config"
```

Define your projects:

```php
return [
    'projects' => [
        'local' => [
            'name' => 'Main Application',
            'connection' => 'mysql',
            'failed_jobs_table' => 'failed_jobs',
            'uses_horizon' => false,
        ],
        'project-api' => [
            'name' => 'API Server',
            'connection' => 'mysql_api',
            'failed_jobs_table' => 'failed_jobs',
            'uses_horizon' => true,
        ],
        'project-worker' => [
            'name' => 'Background Worker',
            'connection' => 'mysql_worker',
            'failed_jobs_table' => 'failed_jobs',
            'uses_horizon' => false,
        ],
    ],
    'spool' => [
        'table' => 'failed_job_action_spool',
    ],
];
```

## Step 4: Run Migrations

### Central Dashboard

```bash
php artisan migrate
```

This creates the `failed_job_action_spool` table.

## Step 5: Configure Remote Projects

### Install Package on Each Remote Project

```bash
composer require srinathreddydudi/failed-jobs
```

### Configure Database Connection

Each remote project needs access to the action spool table. Add a connection to the central dashboard's database in `config/database.php`:

```php
'connections' => [
    'mysql' => [
        // Local connection
    ],
    
    // Connection to central dashboard
    'dashboard' => [
        'driver' => 'mysql',
        'host' => env('DASHBOARD_DB_HOST'),
        'database' => env('DASHBOARD_DB_DATABASE'),
        'username' => env('DASHBOARD_DB_USERNAME'),
        'password' => env('DASHBOARD_DB_PASSWORD'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ],
],
```

### Update Models/FailedJobAction.php

Ensure the model uses the correct connection:

```php
namespace App\Models;

use SrinathReddyDudi\FailedJobs\Models\FailedJobAction as BaseFailedJobAction;

class FailedJobAction extends BaseFailedJobAction
{
    protected $connection = 'dashboard';
}
```

### Add Cron Schedule

In `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('failed-jobs:process-spool --project=project-api --limit=10')
        ->everyMinute()
        ->withoutOverlapping()
        ->onOneServer();
}
```

Replace `project-api` with the project key from your central dashboard config.

## Step 6: Set Up Database Permissions

### Central Dashboard User

The dashboard needs:
- **READ** access to `failed_jobs` table in each remote database
- **READ/WRITE** access to `failed_job_action_spool` table in central database

```sql
GRANT SELECT ON remote_db.failed_jobs TO 'dashboard_readonly'@'dashboard-host';
```

### Remote Project User

Each remote project needs:
- **READ/WRITE** access to `failed_job_action_spool` table in central database

```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON dashboard_db.failed_job_action_spool TO 'remote_project'@'remote-host';
```

## Testing the Setup

### 1. View Failed Jobs

Access the Failed Jobs page in your Filament dashboard. You should see jobs from all configured projects with a "Project" column.

### 2. Test Retry Action

1. Select a failed job from a remote project
2. Click "Retry"
3. Check the `failed_job_action_spool` table - you should see a new pending action
4. Wait for the remote project's cron to run (max 1 minute)
5. Verify the action status changes to "completed"
6. Check that the job was retried in the remote project

### 3. Test Delete Action

1. Select a failed job
2. Click "Delete"
3. Verify the action is created and processed

### 4. Manual Testing

On a remote project, manually trigger the spool processor:

```bash
php artisan failed-jobs:process-spool --project=your-project-key
```

## Monitoring

### Check Action Spool Status

Query the spool table to see pending/failed actions:

```sql
SELECT id, project, action, status, attempts, created_at, error
FROM failed_job_action_spool
WHERE status != 'completed'
ORDER BY created_at DESC;
```

### Monitor Cron Execution

Check your cron logs on remote servers:

```bash
tail -f /var/log/cron.log
```

Or Laravel's schedule log:

```bash
php artisan schedule:work
```

## Troubleshooting

### Jobs Not Appearing

- Verify database connections are working
- Check `failed_jobs` table exists in remote databases
- Ensure project keys in config match database connections

### Actions Not Processing

- Verify cron is running on remote projects
- Check `failed_job_action_spool` table for errors
- Manually run the command to see error messages
- Ensure correct project key is used in schedule

### Permission Errors

- Verify database user permissions
- Check network connectivity between servers
- Test database connections manually

### Actions Stuck in "Processing"

- Indicates the remote worker crashed while processing
- Check logs on the remote server
- You can manually reset status to "pending" to retry:

```sql
UPDATE failed_job_action_spool
SET status = 'pending', attempts = 0
WHERE status = 'processing' AND id = X;
```

## Best Practices

1. **Use Read Replicas** - For large deployments, connect to read replicas of remote databases
2. **Limit Batch Size** - Use `--limit` to prevent overwhelming remote systems
3. **Monitor Spool Growth** - Set up alerts if actions pile up unprocessed
4. **Clean Old Actions** - Periodically delete completed actions older than 30 days
5. **Use Dedicated Users** - Create specific database users with minimal required permissions
6. **Enable SSL** - Use SSL for database connections in production
7. **One Server Only** - Use `onOneServer()` in schedule to prevent duplicate processing

## Advanced Configuration

### Custom Action Spool Table Name

In `config/failed-jobs.php`:

```php
'spool' => [
    'table' => 'custom_action_spool_name',
],
```

### Different Failed Jobs Table Name

If a project uses a different table name:

```php
'projects' => [
    'legacy-project' => [
        'name' => 'Legacy System',
        'connection' => 'mysql_legacy',
        'failed_jobs_table' => 'job_failures', // Custom table name
        'uses_horizon' => false,
    ],
],
```

### Processing Multiple Projects on One Server

If multiple remote projects run on the same server:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('failed-jobs:process-spool --project=project-api --limit=10')
        ->everyMinute()
        ->withoutOverlapping();
    
    $schedule->command('failed-jobs:process-spool --project=project-worker --limit=10')
        ->everyMinute()
        ->withoutOverlapping();
}
```

## Security Considerations

- Never expose database credentials in version control
- Use environment variables for all sensitive data
- Regularly rotate database passwords
- Audit database access logs
- Use VPNs or private networks for database connections
- Implement IP whitelisting on database servers
- Enable query logging for security monitoring

## Support

For issues or questions:
- GitHub Issues: https://github.com/srinathreddydudi/failed-jobs/issues
- Documentation: See README.md for additional information
