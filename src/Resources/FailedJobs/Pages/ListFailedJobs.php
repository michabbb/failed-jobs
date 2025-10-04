<?php

namespace SrinathReddyDudi\FailedJobs\Resources\FailedJobs\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use SrinathReddyDudi\FailedJobs\Enums\FailedJobActionType;
use SrinathReddyDudi\FailedJobs\Support\FailedJobActionDispatcher;
use SrinathReddyDudi\FailedJobs\Support\FailedJobAggregator;
use SrinathReddyDudi\FailedJobs\Support\ProjectRegistry;
use SrinathReddyDudi\FailedJobs\Resources\FailedJobs\FailedJobResource;

class ListFailedJobs extends ListRecords
{
    protected static string $resource = FailedJobResource::class;

    protected function getHeaderActions(): array
    {
        $projects = ProjectRegistry::options();
        $queueOptions = FailedJobAggregator::filterOptions()['queues'];
        $queueOptions = ['all' => __('All queues')] + $queueOptions;

        return [
            Action::make(__('Retry Jobs'))
                ->requiresConfirmation()
                ->schema([
                    Select::make('project')
                        ->label(__('Project'))
                        ->options(['all' => __('All projects')] + $projects)
                        ->default('all')
                        ->required(),
                    Select::make('queue')
                        ->label(__('Queue'))
                        ->options($queueOptions)
                        ->default('all')
                        ->required(),
                ])
                ->successNotificationTitle(__('Jobs pushed to queue successfully!'))
                ->action(function (array $data) use ($projects): void {
                    $targetProjects = $data['project'] === 'all' ? array_keys($projects) : [$data['project']];

                    foreach ($targetProjects as $projectKey) {
                        FailedJobActionDispatcher::dispatch($projectKey, FailedJobActionType::RetryQueue, [
                            'queue' => $data['queue'],
                        ]);
                    }
                }),

            Action::make(__('Prune Jobs'))
                ->requiresConfirmation()
                ->schema([
                    Select::make('project')
                        ->label(__('Project'))
                        ->options(['all' => __('All projects')] + $projects)
                        ->default('all')
                        ->required(),
                    TextInput::make('hours')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->helperText(__("Prune's all failed jobs older than given hours.")),
                ])
                ->color('danger')
                ->successNotificationTitle(__('Jobs pruned successfully!'))
                ->action(function (array $data) use ($projects): void {
                    $targetProjects = $data['project'] === 'all' ? array_keys($projects) : [$data['project']];

                    foreach ($targetProjects as $projectKey) {
                        FailedJobActionDispatcher::dispatch($projectKey, FailedJobActionType::Prune, [
                            'hours' => (int) $data['hours'],
                        ]);
                    }
                }),
        ];
    }
}
