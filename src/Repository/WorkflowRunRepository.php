<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Repository;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowRun;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class WorkflowRunRepository extends EntityRepository
{

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

    /**
     * @return array{totalRuns: int, successRate: float, emailsSent: int, couponsGenerated: int}
     */
    public function getAnalyticsStats(\DateTimeImmutable $since): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) AS totalRuns')
            ->addSelect("SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) AS completedRuns")
            ->where('r.startedAt >= :since')
            ->setParameter('since', $since);

        $result = $qb->getQuery()->getSingleResult();

        $totalRuns = (int) $result['totalRuns'];
        $completedRuns = (int) $result['completedRuns'];
        $successRate = $totalRuns > 0 ? round(($completedRuns / $totalRuns) * 100, 1) : 0;

        // Count action executions from execution logs
        $runs = $this->createQueryBuilder('r')
            ->select('r.executionLog')
            ->where('r.startedAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();

        [$emailsSent, $couponsGenerated] = $this->countActionTypes($runs);

        return [
            'totalRuns' => $totalRuns,
            'successRate' => $successRate,
            'emailsSent' => $emailsSent,
            'couponsGenerated' => $couponsGenerated,
        ];
    }

    /**
     * @return array<array{date: string, count: int}>
     */
    public function getDailyRunCounts(\DateTimeImmutable $since): array
    {
        $metadata = $this->getEntityManager()->getClassMetadata($this->getClassName());
        $tableName = $metadata->getTableName();
        $conn = $this->getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform();

        // Use CAST for PostgreSQL compatibility (DATE works on MySQL/MariaDB, CAST works on both)
        $dateExpr = match (true) {
            str_contains($platform::class, 'PostgreSQL') => 'CAST(started_at AS DATE)',
            default => 'DATE(started_at)',
        };

        $sql = sprintf(
            'SELECT %s AS run_date, COUNT(*) AS run_count FROM %s WHERE started_at >= :since GROUP BY run_date ORDER BY run_date ASC',
            $dateExpr,
            $tableName,
        );

        $rows = $conn->executeQuery($sql, ['since' => $since->format('Y-m-d')])->fetchAllAssociative();

        // Fill gaps with zero counts
        $result = [];
        $current = new \DateTimeImmutable($since->format('Y-m-d'));
        $today = new \DateTimeImmutable('today');
        $countsByDate = [];
        foreach ($rows as $row) {
            $date = $row['run_date'];
            // PostgreSQL CAST returns a Date object, normalize to string
            if ($date instanceof \DateTimeInterface) {
                $date = $date->format('Y-m-d');
            }
            $countsByDate[$date] = (int) $row['run_count'];
        }

        while ($current <= $today) {
            $dateStr = $current->format('Y-m-d');
            $result[] = [
                'date' => $dateStr,
                'count' => $countsByDate[$dateStr] ?? 0,
            ];
            $current = $current->modify('+1 day');
        }

        return $result;
    }

    /**
     * @return array<array{id: int, name: string, runs: int, successRate: float, emails: int, coupons: int}>
     */
    public function getPerCampaignStats(\DateTimeImmutable $since): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.campaign) AS campaignId')
            ->addSelect('c.name')
            ->addSelect('COUNT(r.id) AS totalRuns')
            ->addSelect("SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) AS completedRuns")
            ->join('r.campaign', 'c')
            ->where('r.startedAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('r.campaign, c.name')
            ->orderBy('totalRuns', 'DESC');

        $rows = $qb->getQuery()->getResult();

        // Get execution logs for email/coupon counting per campaign
        $runsByCampaign = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.campaign) AS campaignId, r.executionLog')
            ->where('r.startedAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();

        $emailsByCampaign = [];
        $couponsByCampaign = [];
        foreach ($runsByCampaign as $run) {
            $cId = $run['campaignId'];
            [$emails, $coupons] = $this->countActionTypes([$run]);
            $emailsByCampaign[$cId] = ($emailsByCampaign[$cId] ?? 0) + $emails;
            $couponsByCampaign[$cId] = ($couponsByCampaign[$cId] ?? 0) + $coupons;
        }

        $result = [];
        foreach ($rows as $row) {
            $total = (int) $row['totalRuns'];
            $completed = (int) $row['completedRuns'];
            $cId = $row['campaignId'];

            $result[] = [
                'id' => (int) $cId,
                'name' => $row['name'],
                'runs' => $total,
                'successRate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                'emails' => $emailsByCampaign[$cId] ?? 0,
                'coupons' => $couponsByCampaign[$cId] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Count email and coupon actions from execution log entries.
     * Uses structured actionType field with fallback to message string matching for backward compatibility.
     *
     * @return array{0: int, 1: int} [emailsSent, couponsGenerated]
     */
    private function countActionTypes(array $runs): array
    {
        $emailsSent = 0;
        $couponsGenerated = 0;

        foreach ($runs as $run) {
            foreach ($run['executionLog'] ?? [] as $entry) {
                if (($entry['status'] ?? '') !== 'completed') {
                    continue;
                }

                $actionType = $entry['actionType'] ?? null;
                if ($actionType !== null) {
                    if ($actionType === 'send_email') {
                        $emailsSent++;
                    }
                    if ($actionType === 'generate_coupon') {
                        $couponsGenerated++;
                    }
                    continue;
                }

                // Fallback for logs created before actionType was added
                $message = $entry['message'] ?? '';
                if (str_contains($message, 'Email sent')) {
                    $emailsSent++;
                }
                if (str_contains($message, 'Coupon')) {
                    $couponsGenerated++;
                }
            }
        }

        return [$emailsSent, $couponsGenerated];
    }
}
