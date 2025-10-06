<?php

namespace SrinathReddyDudi\FailedJobs\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use SrinathReddyDudi\FailedJobs\Enums\FailedJobActionStatus;
use SrinathReddyDudi\FailedJobs\Enums\FailedJobActionType;
use SrinathReddyDudi\FailedJobs\Models\FailedJobAction;

class ProcessActionSpoolCommand extends Command
{
    protected $signature = 'failed-jobs:process-spool
                            {--project= : Process only actions for a specific project key}
                            {--limit=10 : Maximum number of actions to process in this run}';

    protected $description = 'Process pending actions from the failed job action spool';

    public function handle(): int
    {
        $projectKey = $this->option('project');
        $limit = (int) $this->option('limit');

        $this->info('Processing failed job action spool...');

        $query = FailedJobAction::where('status', FailedJobActionStatus::Pending->value)
            ->where(function ($query) {
                $query->whereNull('available_at')
                    ->orWhere('available_at', '<=', Carbon::now());
            })
            ->orderBy('id');

        if ($projectKey) {
            $query->where('project', $projectKey);
            $this->info("Filtering for project: {$projectKey}");
        }

        $actions = $query->limit($limit)->get();

        if ($actions->isEmpty()) {
            $this->info('No pending actions found.');

            return self::SUCCESS;
        }

        $this->info("Found {$actions->count()} pending action(s).");

        foreach ($actions as $action) {
            $this->processAction($action);
        }

        return self::SUCCESS;
    }

    protected function processAction(FailedJobAction $action): void
    {
        $this->line("Processing action #{$action->id}: {$action->action} for project {$action->project}");

        $action->update([
            'status' => FailedJobActionStatus::Processing->value,
            'attempts' => $action->attempts + 1,
        ]);

        try {
            $type = FailedJobActionType::from($action->action);
            $payload = $action->payload ?? [];
            $projectConfig = $payload['project'] ?? [];
            $usesHorizon = $projectConfig['uses_horizon'] ?? false;

            match ($type) {
                FailedJobActionType::RetryJobs => $this->retryJobs($payload, $usesHorizon),
                FailedJobActionType::DeleteJobs => $this->deleteJobs($payload, $usesHorizon),
                FailedJobActionType::RetryQueue => $this->retryQueue($payload, $usesHorizon),
                FailedJobActionType::Prune => $this->pruneJobs($payload),
            };

            $action->update([
                'status' => FailedJobActionStatus::Completed->value,
                'processed_at' => Carbon::now(),
                'error' => null,
            ]);

            $this->info("✓ Action #{$action->id} completed successfully.");
        } catch (\Throwable $e) {
            $error = sprintf(
                '%s in %s:%d - %s',
                get_class($e),
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            );

            $action->update([
                'status' => FailedJobActionStatus::Failed->value,
                'processed_at' => Carbon::now(),
                'error' => $error,
            ]);

            $this->error("✗ Action #{$action->id} failed: {$e->getMessage()}");
        }
    }

    protected function retryJobs(array $payload, bool $usesHorizon): void
    {
        $jobs = $payload['jobs'] ?? [];

        foreach ($jobs as $job) {
            $id = $job['id'] ?? $job['uuid'] ?? null;

            if (! $id) {
                continue;
            }

            // Both Horizon and non-Horizon projects use queue:retry
            // Horizon will handle retry through its monitoring
            Artisan::call('queue:retry', ['id' => [$id]]);

            $this->line("  Retried job: {$id}");
        }
    }

    protected function deleteJobs(array $payload, bool $usesHorizon): void
    {
        $jobs = $payload['jobs'] ?? [];

        foreach ($jobs as $job) {
            $id = $job['id'] ?? $job['uuid'] ?? null;

            if (! $id) {
                continue;
            }

            // Both Horizon and non-Horizon projects use queue:forget
            Artisan::call('queue:forget', ['id' => $id]);

            $this->line("  Deleted job: {$id}");
        }
    }

    protected function retryQueue(array $payload, bool $usesHorizon): void
    {
        $queue = $payload['queue'] ?? 'all';

        if ($queue === 'all') {
            Artisan::call('queue:retry', ['id' => ['all']]);
            $this->line('  Retried all jobs');
        } else {
            Artisan::call('queue:retry', ['id' => ['all'], '--queue' => $queue]);
            $this->line("  Retried all jobs in queue: {$queue}");
        }
    }

    protected function pruneJobs(array $payload): void
    {
        $hours = $payload['hours'] ?? 24;

        Artisan::call('queue:prune-failed', ['--hours' => $hours]);

        $this->line("  Pruned jobs older than {$hours} hours");
    }
}
