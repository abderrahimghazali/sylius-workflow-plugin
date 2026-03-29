<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PromotionCouponInterface;
use Sylius\Component\Promotion\Factory\PromotionCouponFactoryInterface;
use Sylius\Component\Promotion\Repository\PromotionRepositoryInterface;

final class GenerateCouponAction implements ActionInterface
{
    public function __construct(
        private readonly PromotionRepositoryInterface $promotionRepository,
        private readonly PromotionCouponFactoryInterface $couponFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'generate_coupon';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $promotionCode = $config['promotion'] ?? '';
        $usageLimit = (int) ($config['usage_limit'] ?? 1);
        $expiresInDays = (int) ($config['expires_in_days'] ?? 30);

        try {
            $promotion = $this->promotionRepository->findOneBy(['code' => $promotionCode]);
            if ($promotion === null) {
                return ['success' => false, 'message' => sprintf('Promotion "%s" not found.', $promotionCode)];
            }

            /** @var PromotionCouponInterface $coupon */
            $coupon = $this->couponFactory->createForPromotion($promotion);
            $coupon->setUsageLimit($usageLimit);
            $coupon->setExpiresAt(new \DateTime(sprintf('+%d days', $expiresInDays)));

            $this->entityManager->persist($coupon);
            $this->entityManager->flush();

            $context->set('coupon', [
                'code' => $coupon->getCode(),
            ]);

            return ['success' => true, 'message' => sprintf('Coupon "%s" generated.', $coupon->getCode())];
        } catch (\Throwable $e) {
            $this->logger->error('Workflow coupon generation failed: ' . $e->getMessage());

            return ['success' => false, 'message' => 'Coupon generation failed: ' . $e->getMessage()];
        }
    }
}
