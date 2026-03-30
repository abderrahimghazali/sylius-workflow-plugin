<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AddLoyaltyPointsAction implements ActionInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'add_loyalty_points';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $amount = (int) ($config['amount'] ?? 0);
        $reason = $config['reason'] ?? 'workflow_action';

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Points amount must be greater than 0.'];
        }

        // Check if loyalty plugin classes exist
        if (!class_exists('Abderrahim\\SyliusLoyaltyPlugin\\Event\\LoyaltyPointsEvent')) {
            $this->logger->warning('Loyalty plugin not installed. Skipping AddLoyaltyPoints action.', [
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return ['success' => false, 'message' => 'Loyalty plugin not installed. Action skipped.'];
        }

        try {
            $eventClass = 'Abderrahim\\SyliusLoyaltyPlugin\\Event\\LoyaltyPointsEvent';
            $event = new $eventClass($context->getSubject(), $amount, $reason);
            $this->eventDispatcher->dispatch($event, 'loyalty.points.add');

            return ['success' => true, 'message' => sprintf('%d loyalty points added.', $amount)];
        } catch (\Throwable $e) {
            $this->logger->error('Workflow loyalty points action failed.', ['exception' => $e]);

            return ['success' => false, 'message' => 'Loyalty points action failed. See server logs for details.'];
        }
    }
}
