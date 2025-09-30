<?php

namespace SrinathReddyDudi\FailedJobs\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use SrinathReddyDudi\FailedJobs\Models\FailedJob;

class DeleteJobAction extends Action
{
    use ManagesJobs;

    public static function getDefaultName(): ?string
    {
        return __('delete');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Delete'))
            ->color('danger')
            ->icon(Heroicon::Trash)
            ->modalHeading(__('Delete failed job?'))
            ->modalDescription(__('Are you sure you want to delete this job?'))
            ->requiresConfirmation()
            ->successNotificationTitle(__('Job deleted!'))
            ->action(function (FailedJob $job) {
                $this->deleteJobs(collect([$job]));
            });
    }
}
