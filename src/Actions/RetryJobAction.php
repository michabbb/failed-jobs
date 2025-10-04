<?php

namespace SrinathReddyDudi\FailedJobs\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class RetryJobAction extends Action
{
    use ManagesJobs;

    public static function getDefaultName(): ?string
    {
        return 'retry';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Retry'))
            ->icon(Heroicon::ArrowPath)
            ->modalHeading(__('Retry failed job?'))
            ->modalDescription(__('Are you sure you want to retry this job?'))
            ->requiresConfirmation()
            ->successNotificationTitle(__('Job pushed to queue successfully!'))
            ->action(function (array $job) {
                $this->retryJobs(collect([$job]));
            });
    }
}
