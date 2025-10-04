<?php

namespace SrinathReddyDudi\FailedJobs\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use SrinathReddyDudi\FailedJobs\FailedJobsPlugin;

class ProjectRegistry
{
    protected static ?Collection $cache = null;

    public static function all(): Collection
    {
        if (static::$cache instanceof Collection) {
            return static::$cache;
        }

        $configured = collect(config('failed-jobs.projects', []));

        if ($configured->isEmpty()) {
            $plugin = FailedJobsPlugin::get();

            $configured = collect([
                'local' => [
                    'name' => config('app.name', 'Local'),
                    'connection' => config('database.default'),
                    'failed_jobs_table' => config('queue.failed.table', 'failed_jobs'),
                    'uses_horizon' => $plugin->isUsingHorizon(),
                ],
            ]);
        }

        static::$cache = $configured->map(function (array $project, string $key): array {
            return [
                'key' => $key,
                'name' => Arr::get($project, 'name', $key),
                'connection' => Arr::get($project, 'connection', config('database.default')),
                'failed_jobs_table' => Arr::get($project, 'failed_jobs_table', config('queue.failed.table', 'failed_jobs')),
                'uses_horizon' => (bool) Arr::get($project, 'uses_horizon', false),
            ];
        });

        return static::$cache;
    }

    public static function get(string $key): ?array
    {
        return static::all()->firstWhere('key', $key);
    }

    public static function options(): array
    {
        return static::all()
            ->mapWithKeys(fn (array $project) => [$project['key'] => $project['name']])
            ->toArray();
    }

    public static function forgetCache(): void
    {
        static::$cache = null;
    }
}
