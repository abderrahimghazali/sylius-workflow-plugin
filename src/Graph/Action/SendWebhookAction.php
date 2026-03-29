<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SendWebhookAction implements ActionInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'send_webhook';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $url = $config['url'] ?? '';
        if ($url === '') {
            return ['success' => false, 'message' => 'No webhook URL specified.'];
        }

        $headers = $config['headers'] ?? [];
        $payload = $this->buildPayload($context, $config);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => $payload,
                'headers' => $headers,
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return ['success' => true, 'message' => sprintf('Webhook sent to %s (HTTP %d).', $url, $statusCode)];
            }

            return ['success' => false, 'message' => sprintf('Webhook returned HTTP %d.', $statusCode)];
        } catch (\Throwable $e) {
            $this->logger->error('Workflow webhook action failed: ' . $e->getMessage(), ['url' => $url]);

            return ['success' => false, 'message' => 'Webhook failed: ' . $e->getMessage()];
        }
    }

    private function buildPayload(WorkflowContext $context, array $config): array
    {
        $payload = [
            'event' => $context->getEvent(),
            'channel' => $context->getChannel(),
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ];

        $subject = $context->getSubject();
        if (method_exists($subject, 'getId')) {
            $payload['subject_id'] = $subject->getId();
        }

        if (isset($config['extra_payload']) && \is_array($config['extra_payload'])) {
            $payload = array_merge($payload, $config['extra_payload']);
        }

        return $payload;
    }
}
