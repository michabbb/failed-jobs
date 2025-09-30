<?php

namespace SrinathReddyDudi\FailedJobs\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use SrinathReddyDudi\FailedJobs\FailedJobsPlugin;

trait ManagesJobs
{
    public function retryJobs(Collection $jobs): void
    {
        Artisan::call('queue:retry ' . $jobs->pluck('uuid')->implode(' '));
    }

    public function deleteJobs(Collection $jobs): void
    {
        foreach ($jobs as $job) {
            if (FailedJobsPlugin::get()->isUsingHorizon()) {
                Artisan::call('horizon:forget ' . $job->uuid);
            } else {
                Artisan::call('queue:forget ' . $job->uuid);
            }
        }
    }
}
