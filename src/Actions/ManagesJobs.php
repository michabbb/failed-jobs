<?php

namespace SrinathReddyDudi\FailedJobs\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use SrinathReddyDudi\FailedJobs\Enums\FailedJobActionType;
use SrinathReddyDudi\FailedJobs\Support\FailedJobActionDispatcher;

trait ManagesJobs
{
    public function retryJobs(Collection $jobs): void
    {
        $grouped = $jobs->groupBy('project_key');

        foreach ($grouped as $projectKey => $projectJobs) {
            FailedJobActionDispatcher::dispatch($projectKey, FailedJobActionType::RetryJobs, [
                'jobs' => $projectJobs->map(fn (array $job) => Arr::only($job, ['id', 'uuid', 'queue', 'connection']))->values()->all(),
            ]);
        }
    }

    public function deleteJobs(Collection $jobs): void
    {
        $grouped = $jobs->groupBy('project_key');

        foreach ($grouped as $projectKey => $projectJobs) {
            FailedJobActionDispatcher::dispatch($projectKey, FailedJobActionType::DeleteJobs, [
                'jobs' => $projectJobs->map(fn (array $job) => Arr::only($job, ['id', 'uuid', 'queue', 'connection']))->values()->all(),
            ]);
        }
    }
}
