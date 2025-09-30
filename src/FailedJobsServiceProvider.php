<?php

namespace SrinathReddyDudi\FailedJobs;

use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
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
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->askToStarRepoOnGitHub('srinathreddydudi/failed-jobs');
            });
    }
}
