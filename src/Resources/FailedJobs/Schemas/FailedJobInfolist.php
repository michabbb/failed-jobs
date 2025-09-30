<?php

namespace SrinathReddyDudi\FailedJobs\Resources\FailedJobs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FailedJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextEntry::make('id'),
                TextEntry::make('uuid'),
                TextEntry::make('connection'),
                TextEntry::make('queue'),

                TextEntry::make('payload')
                    ->formatStateUsing(function ($state) {
                        return json_decode($state, true)['displayName'];
                    })->label('Job'),

                TextEntry::make('failed_at')
                    ->dateTime()
                    ->placeholder('-'),

                TextEntry::make('exception'),

                TextEntry::make('payload')
                    ->formatStateUsing(function ($state) {
                        return '<pre style="overflow-x: auto">' . htmlspecialchars(json_encode(json_decode($state, true), JSON_PRETTY_PRINT)) . '</pre>';
                    })->html(),
            ]);
    }
}
