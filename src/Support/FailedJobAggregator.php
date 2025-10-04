<?php

namespace SrinathReddyDudi\FailedJobs\Support;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FailedJobAggregator
{
    protected static ?Collection $cache = null;

    public static function records(): Collection
    {
        if (static::$cache instanceof Collection) {
            return static::$cache;
        }

        static::$cache = ProjectRegistry::all()
            ->flatMap(function (array $project): Collection {
                $connection = $project['connection'];
                $table = $project['failed_jobs_table'];

                try {
                    $jobs = DB::connection($connection)
                        ->table($table)
                        ->get();
                } catch (\Throwable $exception) {
                    return collect();
                }

                return collect($jobs)->map(function ($job) use ($project): array {
                    $attributes = (array) $job;
                    $payload = json_decode($attributes['payload'] ?? '{}', true) ?: [];

                    $key = sprintf('%s::%s', $project['key'], $attributes['uuid'] ?? $attributes['id']);

                    return [
                        'key' => $key,
                        'project_key' => $project['key'],
                        'project_name' => $project['name'],
                        'project_uses_horizon' => $project['uses_horizon'],
                        'project_connection' => $project['connection'],
                        'failed_jobs_table' => $project['failed_jobs_table'],
                        'id' => $attributes['id'] ?? null,
                        'uuid' => $attributes['uuid'] ?? null,
                        'connection' => $attributes['connection'] ?? $project['connection'],
                        'queue' => $attributes['queue'] ?? null,
                        'payload' => $attributes['payload'] ?? '',
                        'payload_display_name' => Arr::get($payload, 'displayName'),
                        'exception' => $attributes['exception'] ?? null,
                        'failed_at' => isset($attributes['failed_at']) ? Carbon::parse($attributes['failed_at']) : null,
                        'available_at' => isset($attributes['available_at']) ? Carbon::parse($attributes['available_at']) : null,
                    ];
                });
            })
            ->values();

        return static::$cache;
    }

    public static function clearCache(): void
    {
        static::$cache = null;
    }

    public static function resolve(array $keys): array
    {
        $records = static::records();

        return $records
            ->whereIn('key', $keys)
            ->values()
            ->map(fn (array $record) => $record)
            ->all();
    }

    public static function paginate(
        ?string $sortColumn,
        ?string $sortDirection,
        ?string $search,
        array $filters,
        int $page,
        int $recordsPerPage
    ): LengthAwarePaginator {
        $records = static::records();

        $records = static::applyFilters($records, $filters);
        $records = static::applySearch($records, $search);
        $records = static::applySort($records, $sortColumn, $sortDirection);

        $total = $records->count();

        $records = $records
            ->forPage($page, $recordsPerPage)
            ->values();

        return new LengthAwarePaginator(
            $records,
            $total,
            $recordsPerPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
            ],
        );
    }

    protected static function applyFilters(Collection $records, array $filters): Collection
    {
        $project = Arr::get($filters, 'project.project');
        $connection = Arr::get($filters, 'connection.connection');
        $queue = Arr::get($filters, 'queue.queue');
        $job = Arr::get($filters, 'job.job');
        $failedAt = Arr::get($filters, 'failed_at.failed_at');

        return $records
            ->when($project, fn (Collection $collection, $value) => $collection->where('project_key', $value))
            ->when($connection, fn (Collection $collection, $value) => $collection->where('connection', $value))
            ->when($queue, fn (Collection $collection, $value) => $collection->where('queue', $value))
            ->when($job, fn (Collection $collection, $value) => $collection->where('payload_display_name', $value))
            ->when($failedAt, function (Collection $collection, $value): Collection {
                $date = Carbon::parse($value)->startOfDay();

                return $collection->filter(function (array $record) use ($date): bool {
                    $failedAt = $record['failed_at'];

                    return $failedAt instanceof Carbon && $failedAt->greaterThanOrEqualTo($date);
                });
            });
    }

    protected static function applySearch(Collection $records, ?string $search): Collection
    {
        if (blank($search)) {
            return $records;
        }

        $search = Str::lower($search);

        return $records->filter(function (array $record) use ($search): bool {
            $haystack = [
                $record['id'],
                $record['uuid'],
                $record['connection'],
                $record['queue'],
                $record['payload_display_name'],
                $record['exception'],
                $record['project_name'],
            ];

            return collect($haystack)
                ->filter()
                ->contains(function ($value) use ($search) {
                    return Str::contains(Str::lower((string) $value), $search);
                });
        });
    }

    protected static function applySort(Collection $records, ?string $column, ?string $direction): Collection
    {
        if (blank($column)) {
            return $records->sortByDesc('failed_at')->values();
        }

        $descending = strtolower((string) $direction) === 'desc';

        return $records->sortBy($column, SORT_NATURAL, $descending)->values();
    }

    public static function filterOptions(): array
    {
        $records = static::records();

        return [
            'projects' => ProjectRegistry::options(),
            'connections' => $records->pluck('connection', 'connection')->filter()->unique()->map(fn ($value) => ucfirst($value))->toArray(),
            'queues' => $records->pluck('queue', 'queue')->filter()->unique()->map(fn ($value) => ucfirst($value))->toArray(),
            'jobs' => $records->pluck('payload_display_name', 'payload_display_name')->filter()->unique()->toArray(),
        ];
    }
}
