<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Node;

use Abderrahim\SyliusWorkflowPlugin\Enum\NodeType;

final class TriggerNode
{
    public function __construct(
        private readonly string $id,
        private readonly string $event,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            event: $data['config']['event'] ?? '',
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getType(): NodeType
    {
        return NodeType::Trigger;
    }
}
