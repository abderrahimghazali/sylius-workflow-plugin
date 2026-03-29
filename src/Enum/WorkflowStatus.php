<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Enum;

enum WorkflowStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
}
