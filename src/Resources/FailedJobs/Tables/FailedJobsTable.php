<?php

namespace SrinathReddyDudi\FailedJobs\Resources\FailedJobs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use SrinathReddyDudi\FailedJobs\Actions\DeleteJobAction;
use SrinathReddyDudi\FailedJobs\Actions\DeleteJobsBulkAction;
use SrinathReddyDudi\FailedJobs\Actions\RetryJobAction;
use SrinathReddyDudi\FailedJobs\Actions\RetryJobsBulkAction;
use SrinathReddyDudi\FailedJobs\Actions\ViewJobAction;
use SrinathReddyDudi\FailedJobs\FailedJobsPlugin;
use SrinathReddyDudi\FailedJobs\Support\FailedJobAggregator;

class FailedJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->records(function (
                ?string $sortColumn,
                ?string $sortDirection,
                ?string $search,
                array $filters,
                int $page,
                int $recordsPerPage
            ): LengthAwarePaginator {
                return FailedJobAggregator::paginate(
                    $sortColumn,
                    $sortDirection,
                    $search,
                    $filters,
                    $page,
                    $recordsPerPage,
                );
            })
            ->resolveSelectedRecordsUsing(fn (array $keys): array => FailedJobAggregator::resolve($keys))
            ->columns(array_filter([
                TextColumn::make('id')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('project_name')
                    ->label(__('Project'))
                    ->sortable(),

                FailedJobsPlugin::get()->hideConnectionOnIndex ? null : TextColumn::make('connection')->label(__('Connection'))->sortable(),

                FailedJobsPlugin::get()->hideQueueOnIndex ? null : TextColumn::make('queue')->label(__('Queue'))->sortable(),

                TextColumn::make('payload_display_name')
                    ->label(__('Job'))
                    ->searchable()
                    ->wrap(),

                TextColumn::make('exception')->wrap()->limit(100),

                TextColumn::make('failed_at')
                    ->dateTime()
                    ->sortable(),
            ]))
            ->filters(self::getFiltersForIndex(), FailedJobsPlugin::get()->getFiltersLayout())
            ->recordActions([
                RetryJobAction::make()->iconButton()->tooltip(__('Retry Job')),
                ViewJobAction::make()->iconButton()->tooltip(__('View Job')),
                DeleteJobAction::make()->iconButton()->tooltip(__('Delete Job')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RetryJobsBulkAction::make(),
                    DeleteJobsBulkAction::make(),
                ]),
            ])
            ->searchable();
    }

    private static function getFiltersForIndex(): array
    {
        $options = FailedJobAggregator::filterOptions();

        return [
            Filter::make('project')
                ->label(__('Project'))
                ->form([
                    Select::make('project')
                        ->options($options['projects'])
                        ->searchable()
                        ->placeholder(__('All projects')),
                ])
                ->indicateUsing(fn (array $data): ?string => self::indicatorFromOptions($data['project'] ?? null, $options['projects'])),

            Filter::make('connection')
                ->label(__('Connection'))
                ->form([
                    Select::make('connection')
                        ->options($options['connections'])
                        ->placeholder(__('All connections')),
                ])
                ->indicateUsing(fn (array $data): ?string => self::indicatorFromOptions($data['connection'] ?? null, $options['connections'])),

            Filter::make('queue')
                ->label(__('Queue'))
                ->form([
                    Select::make('queue')
                        ->options($options['queues'])
                        ->placeholder(__('All queues')),
                ])
                ->indicateUsing(fn (array $data): ?string => self::indicatorFromOptions($data['queue'] ?? null, $options['queues'])),

            Filter::make('job')
                ->label(__('Job'))
                ->form([
                    Select::make('job')
                        ->options($options['jobs'])
                        ->searchable()
                        ->placeholder(__('All jobs')),
                ])
                ->indicateUsing(fn (array $data): ?string => $data['job'] ?? null),

            Filter::make('failed_at')
                ->label(__('Failed at'))
                ->form([
                    DatePicker::make('failed_at'),
                ])
                ->indicateUsing(fn (array $data): ?string => $data['failed_at'] ?? null),
        ];
    }

    private static function indicatorFromOptions(?string $value, array $options): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Arr::get($options, $value, $value);
    }
}
