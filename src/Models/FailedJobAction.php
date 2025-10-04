<?php

namespace SrinathReddyDudi\FailedJobs\Models;

use Illuminate\Database\Eloquent\Model;

class FailedJobAction extends Model
{
    protected $fillable = [
        'project',
        'action',
        'payload',
        'status',
        'attempts',
        'available_at',
        'processed_at',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('failed-jobs.spool.table', 'failed_job_action_spool');
    }
}
