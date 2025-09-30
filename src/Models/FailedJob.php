<?php

namespace SrinathReddyDudi\FailedJobs\Models;

use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    protected $casts = [
        'payload' => 'string',
    ];

    public function getTable()
    {
        return config('queue.failed.table');
    }
}
