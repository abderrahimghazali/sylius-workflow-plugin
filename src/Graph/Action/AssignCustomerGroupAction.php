<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class AssignCustomerGroupAction implements ActionInterface
{
    public function __construct(
        private readonly RepositoryInterface $customerGroupRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'assign_customer_group';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $groupCode = $config['group_code'] ?? '';
        if ($groupCode === '') {
            return ['success' => false, 'message' => 'No customer group code specified.'];
        }

        $customer = $this->resolveCustomer($context);
        if ($customer === null) {
            return ['success' => false, 'message' => 'No customer found in context.'];
        }

        $group = $this->customerGroupRepository->findOneBy(['code' => $groupCode]);
        if ($group === null) {
            return ['success' => false, 'message' => sprintf('Customer group "%s" not found.', $groupCode)];
        }

        if (!method_exists($customer, 'setGroup')) {
            return ['success' => false, 'message' => 'Customer entity does not support groups.'];
        }

        $customer->setGroup($group);
        $this->entityManager->flush();

        return ['success' => true, 'message' => sprintf('Customer assigned to group "%s".', $groupCode)];
    }

    private function resolveCustomer(WorkflowContext $context): ?object
    {
        $subject = $context->getSubject();

        if (method_exists($subject, 'getEmail') && method_exists($subject, 'setGroup')) {
            return $subject;
        }

        if ($subject instanceof OrderInterface && $subject->getCustomer() !== null) {
            return $subject->getCustomer();
        }

        return null;
    }
}
