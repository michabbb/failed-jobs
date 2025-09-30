<?php

namespace SrinathReddyDudi\FailedJobs\Resources\FailedJobs\Pages;

use Filament\Resources\Pages\ViewRecord;
use SrinathReddyDudi\FailedJobs\Actions\DeleteJobAction;
use SrinathReddyDudi\FailedJobs\Actions\RetryJobAction;
use SrinathReddyDudi\FailedJobs\Resources\FailedJobs\FailedJobResource;

class ViewFailedJob extends ViewRecord
{
    protected static string $resource = FailedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RetryJobAction::make()->successRedirectUrl($this->getResourceUrl('index')),
            DeleteJobAction::make()->successRedirectUrl($this->getResourceUrl('index')),
        ];
    }
}
