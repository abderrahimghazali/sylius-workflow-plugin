<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Node;

use Abderrahim\SyliusWorkflowPlugin\Enum\NodeType;

final class ActionNode
{
    public function __construct(
        private readonly string $id,
        private readonly string $actionType,
        private readonly array $config = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $config = $data['config'] ?? [];
        $type = $config['type'] ?? '';
        unset($config['type']);

        return new self(
            id: $data['id'],
            actionType: $type,
            config: $config,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getType(): NodeType
    {
        return NodeType::Action;
    }
}
