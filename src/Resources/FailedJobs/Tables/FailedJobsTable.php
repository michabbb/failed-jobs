<?php

namespace SrinathReddyDudi\FailedJobs\Resources\FailedJobs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use SrinathReddyDudi\FailedJobs\Actions\DeleteJobAction;
use SrinathReddyDudi\FailedJobs\Actions\DeleteJobsBulkAction;
use SrinathReddyDudi\FailedJobs\Actions\RetryJobAction;
use SrinathReddyDudi\FailedJobs\Actions\RetryJobsBulkAction;
use SrinathReddyDudi\FailedJobs\FailedJobsPlugin;
use SrinathReddyDudi\FailedJobs\Models\FailedJob;

class FailedJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(array_filter([
                TextColumn::make('id')
                    ->numeric()
                    ->sortable(),

                FailedJobsPlugin::get()->hideConnectionOnIndex ? null : TextColumn::make('connection')->searchable(),

                FailedJobsPlugin::get()->hideQueueOnIndex ? null : TextColumn::make('queue')->searchable(),

                TextColumn::make('payload')->label('Job')
                    ->formatStateUsing(function ($state) {
                        return json_decode($state, true)['displayName'];
                    })->searchable(),

                TextColumn::make('exception')->wrap()->limit(100),

                TextColumn::make('failed_at')->searchable(),
            ]))
            ->filters(self::getFiltersForIndex(), FailedJobsPlugin::get()->getFiltersLayout())
            ->recordActions([
                RetryJobAction::make()->iconButton()->tooltip(__('Retry Job')),
                ViewAction::make()->iconButton()->tooltip(__('View Job')),
                DeleteJobAction::make()->iconButton()->tooltip(__('Delete Job')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RetryJobsBulkAction::make(),
                    DeleteJobsBulkAction::make(),
                ]),
            ]);
    }

    private static function getFiltersForIndex(): array
    {
        $jobs = FailedJob::query()
            ->select(['connection', 'queue'])
            ->selectRaw("payload->>'$.displayName' AS job")
            ->get();

        $connections = $jobs->pluck('connection', 'connection')->map(fn ($conn) => ucfirst($conn))->toArray();
        $queues = $jobs->pluck('queue', 'queue')->map(fn ($queue) => ucfirst($queue))->toArray();
        $jobNames = $jobs->pluck('job', 'job')->toArray();

        return [
            SelectFilter::make('Connection')->options($connections),
            SelectFilter::make('Queue')->options($queues),
            Filter::make('Job')
                ->schema([
                    Select::make('job')->options($jobNames),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['job'],
                            fn (Builder $query, $job): Builder => $query->whereRaw("payload->>'$.displayName' = ?", [$job]),
                        );
                }),
            Filter::make('failed_at')
                ->schema([
                    DatePicker::make('failed_at'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['failed_at'],
                            fn (Builder $query, $date): Builder => $query->whereDate('failed_at', '>=', $date),
                        );
                }),
        ];
    }
}
