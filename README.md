# A Filament Plugin to Retry and manage failed jobs

[![Latest Version on Packagist](https://img.shields.io/packagist/v/srinathreddydudi/failed-jobs.svg?style=flat-square)](https://packagist.org/packages/srinathreddydudi/failed-jobs)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/srinathreddydudi/failed-jobs/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/srinathreddydudi/failed-jobs/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/srinathreddydudi/failed-jobs/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/srinathreddydudi/failed-jobs/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/srinathreddydudi/failed-jobs.svg?style=flat-square)](https://packagist.org/packages/srinathreddydudi/failed-jobs)

This plugin provides a failed jobs resource which can be used to retry and manage Laravel failed queue jobs.

> [!NOTE]
> The plugin can aggregate failed jobs from multiple Laravel projects. Configure the remote projects inside `config/failed-jobs.php` and point each entry to the database connection that exposes its `failed_jobs` table. All retry and delete actions are dispatched through an action spool table so that the remote systems can execute them locally.

![failed jobs index table](/resources/screenshots/index.png)

## Installation

You can install the plugin via composer:

```bash
composer require srinathreddydudi/failed-jobs
```

## Usage

Register the plugin in your panel service provider as

```php
$panel->plugin(FailedJobsPlugin::make());
```
> [!IMPORTANT]
> If you are using laravel horizon, Instruct the plugin by chaining the `->usingHorizon()` method.

## Multi-Project Setup

The plugin can aggregate failed jobs from multiple Laravel projects. This is useful when you have several applications and want to manage all failed jobs from a single dashboard.

### Central Dashboard Configuration

In your central dashboard project, publish and configure the plugin:

```bash
php artisan vendor:publish --tag="failed-jobs-config"
```

Edit `config/failed-jobs.php` and define your projects:

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

Each project entry requires:
- `name`: Human-readable project name shown in the dashboard
- `connection`: Database connection name that can access the project's `failed_jobs` table
- `failed_jobs_table`: Name of the failed jobs table (usually `failed_jobs`)
- `uses_horizon`: Whether the project uses Laravel Horizon

### Database Connections

Configure database connections in your `config/database.php` to access each project's database:

```php
'connections' => [
    'mysql' => [
        // Your default connection
    ],
    'mysql_api' => [
        'driver' => 'mysql',
        'host' => env('DB_API_HOST'),
        'database' => env('DB_API_DATABASE'),
        'username' => env('DB_API_USERNAME'),
        'password' => env('DB_API_PASSWORD'),
        // ...
    ],
    'mysql_worker' => [
        'driver' => 'mysql',
        'host' => env('DB_WORKER_HOST'),
        'database' => env('DB_WORKER_DATABASE'),
        'username' => env('DB_WORKER_USERNAME'),
        'password' => env('DB_WORKER_PASSWORD'),
        // ...
    ],
],
```

> [!IMPORTANT]
> **Security Considerations:**
> - The central dashboard needs READ access to each project's `failed_jobs` table
> - The central dashboard needs READ/WRITE access to the `failed_job_action_spool` table
> - Each remote project needs READ/WRITE access to the `failed_job_action_spool` table
> - Use dedicated database users with minimal required permissions
> - Consider using read replicas for large-scale deployments

### Remote Project Setup

Each remote Laravel project needs to process actions from the spool table. Run the migration on your central dashboard:

```bash
php artisan migrate
```

On each remote project, add a scheduled task to process the action spool:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Process action spool every minute
    $schedule->command('failed-jobs:process-spool --project=project-key --limit=10')
        ->everyMinute()
        ->withoutOverlapping();
}
```

Replace `project-key` with the key you defined in your central dashboard's config (e.g., `project-api`, `project-worker`).

The `--limit` option controls how many actions to process per run. Adjust based on your needs.

### How It Works

1. When you retry or delete failed jobs from the dashboard, actions are written to the `failed_job_action_spool` table
2. Each remote project's cron job picks up pending actions for that project
3. The remote project executes the action locally (retry/delete) using its own queue configuration
4. The action status is updated in the spool table

This approach ensures that:
- Queue operations run in the correct project context
- Remote systems maintain full control over their queue drivers
- The dashboard doesn't need direct access to execute artisan commands on remote systems
- All queue operations respect each project's Horizon configuration

## Retrying Failed Jobs
You can retry failed jobs each one separetely using the retry action next to each job, or bulk retry by selecting 
multiple jobs and then using the bulk options' menu. You can also use the global retry action to retry all failed jobs or 
jobs from a specific queue.

![retry failed jobs](/resources/screenshots/retry-modal.png)

## Filtering Jobs
This plugin by default comes with the following filters which you can use to 
filter failed jobs.
- Connection
- Queue
- Job
- Failed At

![filter failed jobs](/resources/screenshots/filters.png)

## Pruning Jobs
If you have too many stale failed jobs, You can use the global prune jobs action to prune stale failed jobs. 
This action will prompt you to input the hours to retain the failed jobs. Any failed jobs that are older than the 
given hours will be pruned.

For example, If you enter 12 hours, It will prune all failed jobs which are older than 12 hours.

![retry failed jobs](/resources/screenshots/prune-modal.png)

## Action Spool Command

The `failed-jobs:process-spool` command processes pending actions from the action spool table:

```bash
# Process all pending actions (limit 10 per run)
php artisan failed-jobs:process-spool

# Process only actions for a specific project
php artisan failed-jobs:process-spool --project=project-api

# Process more actions per run
php artisan failed-jobs:process-spool --limit=50

# Combine options
php artisan failed-jobs:process-spool --project=project-worker --limit=20
```

Options:
- `--project`: Filter actions for a specific project key (as defined in config)
- `--limit`: Maximum number of actions to process in this run (default: 10)

The command will:
1. Find pending actions for the specified project (or all projects)
2. Execute each action (retry/delete jobs, prune old jobs, etc.)
3. Update the action status (completed or failed)
4. Log any errors for debugging

## Customization
This plugin works out of the box and adds a `Failed Jobs` resource to your admin panel. You can customize the
display if needed.

### Remove connection column from index table
Most of the applications do not leverage more than one queue connection. So it would be clean to hide the connection
column in this case. You can do so by chaining the `hideConnectionOnIndex` method as below.

```php
FailedJobsPlugin::make()->hideConnectionOnIndex()
```

### Remove queue column from index table
Similarly, if your application only pushes to the default queue, You can hide the queue column by chaining the `hideQueueOnIndex` method as below.

```php
FailedJobsPlugin::make()->hideQueueOnIndex()
```

### Change filters layout
This plugin comes with a few filters to help you easily filter failed jobs. If you would like to change how the
filters are displayed, You can do so by chaining `filtersLayout` method which
accepts `Filament\Tables\Enums\FiltersLayout` parameter.

```php
FailedJobsPlugin::make()->filtersLayout(FiltersLayout::AboveContent)
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Srinath Reddy Dudi](https://github.com/srinathreddydudi)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
