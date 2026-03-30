<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\PromotionCouponInterface;
use Sylius\Component\Core\Model\PromotionInterface;
use Sylius\Component\Promotion\Factory\PromotionCouponFactoryInterface;
use Sylius\Component\Promotion\Repository\PromotionRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class GenerateCouponAction implements ActionInterface
{
    public function __construct(
        private readonly PromotionRepositoryInterface $promotionRepository,
        private readonly PromotionCouponFactoryInterface $couponFactory,
        private readonly FactoryInterface $promotionFactory,
        private readonly FactoryInterface $promotionActionFactory,
        private readonly ChannelRepositoryInterface $channelRepository,
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
        $discount = (int) ($config['discount'] ?? 10);

        if ($promotionCode === '') {
            return ['success' => false, 'message' => 'No promotion code specified.'];
        }

        try {
            /** @var PromotionInterface|null $promotion */
            $promotion = $this->promotionRepository->findOneBy(['code' => $promotionCode]);

            if ($promotion === null) {
                $promotion = $this->createPromotion($promotionCode, $discount);
            }

            /** @var PromotionCouponInterface $coupon */
            $coupon = $this->couponFactory->createForPromotion($promotion);
            $coupon->setCode($promotionCode . '-' . strtoupper(bin2hex(random_bytes(4))));
            $coupon->setUsageLimit($usageLimit);
            $coupon->setExpiresAt(new \DateTime(sprintf('+%d days', $expiresInDays)));

            $this->entityManager->persist($coupon);
            $this->entityManager->flush();

            $context->set('coupon', [
                'code' => $coupon->getCode(),
            ]);

            return ['success' => true, 'message' => sprintf('Coupon "%s" generated.', $coupon->getCode())];
        } catch (\Throwable $e) {
            $this->logger->error('Workflow coupon generation failed.', ['exception' => $e]);

            return ['success' => false, 'message' => 'Coupon generation failed. See server logs for details.'];
        }
    }

    private function createPromotion(string $code, int $discountPercent): PromotionInterface
    {
        /** @var PromotionInterface $promotion */
        $promotion = $this->promotionFactory->createNew();
        $promotion->setCode($code);
        $promotion->setName('Workflow: ' . $code);
        $promotion->setCouponBased(true);
        $promotion->setPriority(0);
        $promotion->setExclusive(false);

        // Add to all channels
        $channels = $this->channelRepository->findAll();
        foreach ($channels as $channel) {
            /** @var \Sylius\Component\Channel\Model\ChannelInterface $channel */
            $promotion->addChannel($channel);
        }

        // Add percentage discount action
        /** @var \Sylius\Component\Promotion\Model\PromotionActionInterface $action */
        $action = $this->promotionActionFactory->createNew();
        $action->setType('order_percentage_discount');
        $action->setConfiguration(['percentage' => $discountPercent / 100]);
        $promotion->addAction($action);

        $this->entityManager->persist($promotion);
        $this->entityManager->flush();

        $this->logger->info(sprintf('Workflow auto-created promotion "%s" with %d%% discount.', $code, $discountPercent));

        return $promotion;
    }
}
