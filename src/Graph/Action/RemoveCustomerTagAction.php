<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class RemoveCustomerTagAction implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'remove_customer_tag';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $tag = $config['tag'] ?? '';
        if ($tag === '') {
            return ['success' => false, 'message' => 'No tag specified.'];
        }

        $customer = $this->resolveCustomer($context);
        if ($customer === null) {
            return ['success' => false, 'message' => 'No customer found in context.'];
        }

        if (!method_exists($customer, 'removeTag')) {
            return ['success' => false, 'message' => 'Customer entity does not support tags.'];
        }

        $customer->removeTag($tag);
        $this->entityManager->flush();

        return ['success' => true, 'message' => sprintf('Tag "%s" removed from customer.', $tag)];
    }

    private function resolveCustomer(WorkflowContext $context): ?CustomerInterface
    {
        $subject = $context->getSubject();

        if ($subject instanceof CustomerInterface) {
            return $subject;
        }

        if ($subject instanceof OrderInterface) {
            return $subject->getCustomer();
        }

        return null;
    }
}
