<?php

namespace SrinathReddyDudi\FailedJobs\Support;

use SrinathReddyDudi\FailedJobs\Enums\FailedJobActionStatus;
use SrinathReddyDudi\FailedJobs\Enums\FailedJobActionType;
use SrinathReddyDudi\FailedJobs\Models\FailedJobAction;
use SrinathReddyDudi\FailedJobs\Support\ProjectRegistry;

class FailedJobActionDispatcher
{
    public static function dispatch(string $projectKey, FailedJobActionType $type, array $payload = []): void
    {
        $project = ProjectRegistry::get($projectKey);

        if (! $project) {
            return;
        }

        FailedJobAction::create([
            'project' => $projectKey,
            'action' => $type->value,
            'payload' => array_merge($payload, [
                'project' => [
                    'name' => $project['name'],
                    'uses_horizon' => $project['uses_horizon'],
                ],
            ]),
            'status' => FailedJobActionStatus::Pending->value,
        ]);
    }
}
