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

        $format = $config['format'] ?? 'json';
        $message = $config['message'] ?? '';
        $payload = $this->buildPayload($context, $format, $message);

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

        if (\in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '[::1]', '169.254.169.254'], true)) {
            return false;
        }

        $ip = gethostbyname($host);
        if ($ip === $host) {
            return false;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        return true;
    }

    private function buildPayload(WorkflowContext $context, string $format, string $message): array
    {
        $text = $this->resolveMessage($context, $message);

        return match ($format) {
            'discord' => ['content' => $text],
            'slack' => ['text' => $text],
            default => [
                'event' => $context->getEvent(),
                'channel' => $context->getChannel(),
                'message' => $text,
                'timestamp' => (new \DateTimeImmutable())->format('c'),
                'subject_id' => method_exists($context->getSubject(), 'getId') ? $context->getSubject()->getId() : null,
            ],
        };
    }

    private function resolveMessage(WorkflowContext $context, string $template): string
    {
        if ($template === '') {
            $template = 'Workflow "{workflow}" triggered by {event} for subject #{subject_id}';
        }

        $subject = $context->getSubject();
        $subjectId = method_exists($subject, 'getId') ? (string) $subject->getId() : '?';

        $replacements = [
            '{event}' => $context->getEvent(),
            '{channel}' => $context->getChannel(),
            '{subject_id}' => $subjectId,
            '{workflow}' => $context->get('workflow_name', 'Workflow'),
        ];

        if (method_exists($subject, 'getNumber')) {
            $replacements['{order_number}'] = (string) $subject->getNumber();
        }

        if (method_exists($subject, 'getCustomer') && $subject->getCustomer() !== null) {
            $customer = $subject->getCustomer();
            $replacements['{customer_email}'] = method_exists($customer, 'getEmail') ? $customer->getEmail() : '';
            $replacements['{customer_name}'] = method_exists($customer, 'getFullName') ? $customer->getFullName() : '';
        } elseif (method_exists($subject, 'getEmail')) {
            $replacements['{customer_email}'] = $subject->getEmail();
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
