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

        if (!$this->isUrlSafe($url)) {
            return ['success' => false, 'message' => 'Webhook URL is not allowed (must be public HTTPS).'];
        }

        $payload = $this->buildPayload($context);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => $payload,
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return ['success' => true, 'message' => sprintf('Webhook sent (HTTP %d).', $statusCode)];
            }

            return ['success' => false, 'message' => sprintf('Webhook returned HTTP %d.', $statusCode)];
        } catch (\Throwable $e) {
            $this->logger->error('Workflow webhook action failed.', ['url' => $url, 'exception' => $e]);

            return ['success' => false, 'message' => 'Webhook request failed. See server logs for details.'];
        }
    }

    private function isUrlSafe(string $url): bool
    {
        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            return false;
        }

        if (!\in_array($parsed['scheme'], ['https', 'http'], true)) {
            return false;
        }

        $host = $parsed['host'];

        // Block loopback, link-local, and metadata endpoints
        if (\in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '[::1]', '169.254.169.254'], true)) {
            return false;
        }

        // Resolve hostname and check for private IP ranges
        $ip = gethostbyname($host);
        if ($ip === $host) {
            return false; // DNS resolution failed
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        return true;
    }

    private function buildPayload(WorkflowContext $context): array
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

        return $payload;
    }
}
