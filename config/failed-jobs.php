<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Project Connections
    |--------------------------------------------------------------------------
    |
    | Define every Laravel project that should appear in the failed jobs table.
    | Each project must expose the database connection that contains its
    | `failed_jobs` table. The key of the array is used as a stable identifier
    | inside the action spool.
    |
    */
    'projects' => [
        // Leave empty or comment out for single-project mode (backward compatible)
        //
        // Example multi-project configuration:
        //
        // 'local' => [
        //     'name' => 'Main Application',
        //     'connection' => 'mysql',  // Laravel database connection name
        //     'failed_jobs_table' => 'failed_jobs',
        //     'uses_horizon' => false,  // Set to true if this project uses Laravel Horizon
        // ],
        //
        // 'project-api' => [
        //     'name' => 'API Server',
        //     'connection' => 'mysql_api',  // Must be configured in config/database.php
        //     'failed_jobs_table' => 'failed_jobs',
        //     'uses_horizon' => true,
        // ],
        //
        // 'project-worker' => [
        //     'name' => 'Background Worker',
        //     'connection' => 'mysql_worker',
        //     'failed_jobs_table' => 'failed_jobs',
        //     'uses_horizon' => false,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Spool
    |--------------------------------------------------------------------------
    |
    | The action spool table stores pending work that needs to be executed by
    | the remote Laravel projects. Each remote system should poll this table
    | and execute the queued action for its project.
    |
    */
    'spool' => [
        'table' => 'failed_job_action_spool',
    ],
];
