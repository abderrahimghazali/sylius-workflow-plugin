<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Repository;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkflowCampaign>
 */
class WorkflowCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowCampaign::class);
    }

    /**
     * @return WorkflowCampaign[]
     */
    public function findActiveByTriggerEvent(string $eventName): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.enabled = :enabled')
            ->andWhere('c.status = :status')
            ->setParameter('enabled', true)
            ->setParameter('status', WorkflowStatus::Active->value);

        $campaigns = $qb->getQuery()->getResult();

        // Filter campaigns whose trigger node matches the event
        return array_filter($campaigns, function (WorkflowCampaign $campaign) use ($eventName): bool {
            foreach ($campaign->getNodes() as $node) {
                if (($node['type'] ?? '') === 'trigger' && ($node['config']['event'] ?? '') === $eventName) {
                    return true;
                }
            }
            return false;
        });
    }
}
