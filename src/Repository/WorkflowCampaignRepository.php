<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Repository;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class WorkflowCampaignRepository extends EntityRepository
{
    /**
     * @return WorkflowCampaign[]
     */
    public function findActiveByTriggerEvent(string $eventName): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.enabled = :enabled')
            ->andWhere('c.status = :status')
            ->andWhere('c.triggerEvent = :event')
            ->setParameter('enabled', true)
            ->setParameter('status', WorkflowStatus::Active->value)
            ->setParameter('event', $eventName)
            ->getQuery()
            ->getResult();
    }
}
