<?php

namespace SrinathReddyDudi\FailedJobs\Resources\FailedJobs\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use SrinathReddyDudi\FailedJobs\Models\FailedJob;
use SrinathReddyDudi\FailedJobs\Resources\FailedJobs\FailedJobResource;

class ListFailedJobs extends ListRecords
{
    protected static string $resource = FailedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make(__('Retry Jobs'))
                ->requiresConfirmation()
                ->schema(function () {

                    $queues = FailedJob::query()
                        ->select('queue')
                        ->distinct()
                        ->pluck('queue')
                        ->toArray();

                    $options = [
                        'all' => 'All Queues',
                    ];

                    $descriptions = [
                        'all' => 'Retry all Jobs',
                    ];

                    foreach ($queues as $queue) {
                        $options[$queue] = $queue;
                        $descriptions[$queue] = 'Retry jobs from ' . $queue . ' queue';
                    }

                    return [
                        Radio::make('queue')
                            ->options($options)
                            ->descriptions($descriptions)
                            ->default('all')
                            ->required(),
                    ];
                })
                ->successNotificationTitle(__('Jobs pushed to queue successfully!'))
                ->action(fn (array $data) => Artisan::call('queue:retry ' . $data['queue'])),

            Action::make(__('Prune Jobs'))
                ->requiresConfirmation()
                ->schema([
                    TextInput::make('hours')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->helperText(__("Prune's all failed jobs older than given hours.")),
                ])
                ->color('danger')
                ->successNotificationTitle(__('Jobs pruned successfully!'))
                ->action(fn (array $data) => Artisan::call('queue:prune-failed --hours=' . $data['hours'])),
        ];
    }
}
