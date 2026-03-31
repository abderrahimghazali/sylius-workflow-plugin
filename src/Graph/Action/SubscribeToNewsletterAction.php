<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class SubscribeToNewsletterAction implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'subscribe_newsletter';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $customer = $this->resolveCustomer($context);
        if ($customer === null) {
            return ['success' => false, 'message' => 'No customer found in context.'];
        }

        if (!method_exists($customer, 'setSubscribedToNewsletter')) {
            return ['success' => false, 'message' => 'Customer entity does not support newsletter subscription.'];
        }

        $customer->setSubscribedToNewsletter(true);
        $this->entityManager->flush();

        return ['success' => true, 'message' => 'Customer subscribed to newsletter.'];
    }

    private function resolveCustomer(WorkflowContext $context): ?object
    {
        $subject = $context->getSubject();

        if (method_exists($subject, 'getEmail') && method_exists($subject, 'setSubscribedToNewsletter')) {
            return $subject;
        }

        if ($subject instanceof OrderInterface && $subject->getCustomer() !== null) {
            return $subject->getCustomer();
        }

        return null;
    }
}
