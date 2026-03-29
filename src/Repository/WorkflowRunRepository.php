<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Repository;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowRun;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkflowRun>
 */
class WorkflowRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowRun::class);
    }

    public function countByCampaignAndSubject(int $campaignId, int $subjectId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.campaign = :campaignId')
            ->andWhere('r.subjectId = :subjectId')
            ->setParameter('campaignId', $campaignId)
            ->setParameter('subjectId', $subjectId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
