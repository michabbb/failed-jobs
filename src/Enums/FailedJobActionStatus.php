<?php

namespace SrinathReddyDudi\FailedJobs\Enums;

enum FailedJobActionStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
