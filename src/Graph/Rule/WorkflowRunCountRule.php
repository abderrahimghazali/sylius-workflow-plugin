<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Abderrahim\SyliusWorkflowPlugin\Repository\WorkflowRunRepository;

final class WorkflowRunCountRule implements RuleInterface
{
    public function __construct(
        private readonly WorkflowRunRepository $workflowRunRepository,
    ) {
    }

    public function supports(string $rule): bool
    {
        return $rule === 'workflow_run_count';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $campaignId = $context->get('campaign_id');
        $subjectId = $context->get('subject_id');

        if ($campaignId === null || $subjectId === null) {
            return false;
        }

        $count = $this->workflowRunRepository->countByCampaignAndSubject((int) $campaignId, (int) $subjectId);
        $compareValue = (int) $value;

        return match ($operator) {
            'is' => $count === $compareValue,
            'is_not' => $count !== $compareValue,
            'gt' => $count > $compareValue,
            'lt' => $count < $compareValue,
            'gte' => $count >= $compareValue,
            'lte' => $count <= $compareValue,
            default => false,
        };
    }
}
