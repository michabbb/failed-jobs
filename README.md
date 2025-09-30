# A Filament Plugin to Retry and manage failed jobs

[![Latest Version on Packagist](https://img.shields.io/packagist/v/srinathreddydudi/failed-jobs.svg?style=flat-square)](https://packagist.org/packages/srinathreddydudi/failed-jobs)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/srinathreddydudi/failed-jobs/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/srinathreddydudi/failed-jobs/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/srinathreddydudi/failed-jobs/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/srinathreddydudi/failed-jobs/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/srinathreddydudi/failed-jobs.svg?style=flat-square)](https://packagist.org/packages/srinathreddydudi/failed-jobs)

This plugin provides a failed jobs resource which can be used to retry and manage laravel failed queue jobs.

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
