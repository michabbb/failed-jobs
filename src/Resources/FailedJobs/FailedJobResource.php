<?php

namespace SrinathReddyDudi\FailedJobs\Resources\FailedJobs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use SrinathReddyDudi\FailedJobs\Models\FailedJob;
use SrinathReddyDudi\FailedJobs\Resources\FailedJobs\Pages\ListFailedJobs;
use SrinathReddyDudi\FailedJobs\Resources\FailedJobs\Pages\ViewFailedJob;
use SrinathReddyDudi\FailedJobs\Resources\FailedJobs\Schemas\FailedJobInfolist;
use SrinathReddyDudi\FailedJobs\Resources\FailedJobs\Tables\FailedJobsTable;

class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::QueueList;

    public static function infolist(Schema $schema): Schema
    {
        return FailedJobInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FailedJobsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFailedJobs::route('/'),
            'view' => ViewFailedJob::route('/{record}'),
        ];
    }
}
