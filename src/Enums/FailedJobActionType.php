<?php

namespace SrinathReddyDudi\FailedJobs\Enums;

enum FailedJobActionType: string
{
    case RetryJobs = 'retry-jobs';
    case DeleteJobs = 'delete-jobs';
    case RetryQueue = 'retry-queue';
    case Prune = 'prune';
}
