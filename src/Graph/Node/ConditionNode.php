<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Node;

use Abderrahim\SyliusWorkflowPlugin\Enum\NodeType;

final class ConditionNode
{
    public function __construct(
        private readonly string $id,
        private readonly string $rule,
        private readonly string $operator,
        private readonly string $value,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            rule: $data['config']['rule'] ?? '',
            operator: $data['config']['operator'] ?? 'is',
            value: $data['config']['value'] ?? '',
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRule(): string
    {
        return $this->rule;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getType(): NodeType
    {
        return NodeType::Condition;
    }
}
