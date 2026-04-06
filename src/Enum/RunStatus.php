<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Enum;

enum RunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
