<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Enum;

enum NodeType: string
{
    case Trigger = 'trigger';
    case Condition = 'condition';
    case Action = 'action';
    case Delay = 'delay';
}
