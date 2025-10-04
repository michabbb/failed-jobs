<?php

namespace SrinathReddyDudi\FailedJobs;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FailedJobsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'failed-jobs';

    public static string $viewNamespace = 'failed-jobs';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_failed_job_action_spool_table')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->askToStarRepoOnGitHub('srinathreddydudi/failed-jobs');
            });
    }
}
