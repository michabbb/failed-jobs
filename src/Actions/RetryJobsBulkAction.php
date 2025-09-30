<?php

namespace SrinathReddyDudi\FailedJobs\Actions;

use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class RetryJobsBulkAction extends BulkAction
{
    use ManagesJobs;

    public static function getDefaultName(): ?string
    {
        return 'retry jobs';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Retry Jobs'))
            ->color('primary')
            ->requiresConfirmation()
            ->accessSelectedRecords()
            ->icon(Heroicon::ArrowPath)
            ->modalHeading(__('Retry failed jobs?'))
            ->modalDescription(__('Are you sure you want to retry these jobs?'))
            ->successNotificationTitle(__('Jobs pushed to queue successfully!'))
            ->action(function (Collection $jobs) {
                $this->retryJobs($jobs);
            });
    }
}
