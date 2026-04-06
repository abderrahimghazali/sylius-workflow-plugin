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
        private readonly int $timeout = 10,
        private readonly bool $allowHttp = false,
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
                'timeout' => $this->timeout,
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

        $allowedSchemes = $this->allowHttp ? ['https', 'http'] : ['https'];
        if (!\in_array($parsed['scheme'], $allowedSchemes, true)) {
            return false;
        }

        $host = $parsed['host'];

        // Block well-known private/loopback hostnames
        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '[::1]', '169.254.169.254'];
        if (\in_array($host, $blockedHosts, true)) {
            return false;
        }

        // Resolve all IPs for the hostname and validate each one
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false || $records === []) {
            return false;
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip === null) {
                continue;
            }

            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    private function buildPayload(WorkflowContext $context, string $format, string $message): array
    {
        $text = $this->resolveMessage($context, $message);

        return match ($format) {
            'discord' => $this->buildDiscordEmbed($context, $text),
            'slack' => $this->buildSlackPayload($context, $text),
            default => [
                'event' => $context->getEvent(),
                'channel' => $context->getChannel(),
                'message' => $text,
                'timestamp' => (new \DateTimeImmutable())->format('c'),
                'subject_id' => method_exists($context->getSubject(), 'getId') ? $context->getSubject()->getId() : null,
            ],
        };
    }

    private function buildDiscordEmbed(WorkflowContext $context, string $description): array
    {
        $subject = $context->getSubject();
        $fields = [];

        if (method_exists($subject, 'getNumber')) {
            $fields[] = ['name' => '🛒 Order', 'value' => '#' . $subject->getNumber(), 'inline' => true];
        }

        $customer = null;
        if (method_exists($subject, 'getCustomer') && $subject->getCustomer() !== null) {
            $customer = $subject->getCustomer();
        } elseif (method_exists($subject, 'getEmail')) {
            $customer = $subject;
        }

        if ($customer !== null && method_exists($customer, 'getEmail')) {
            $fields[] = ['name' => '👤 Customer', 'value' => $customer->getEmail(), 'inline' => true];
        }

        $fields[] = ['name' => '📡 Channel', 'value' => $context->getChannel(), 'inline' => true];

        if (method_exists($subject, 'getTotal')) {
            $total = $subject->getTotal();
            $currency = method_exists($subject, 'getCurrencyCode') ? $subject->getCurrencyCode() : 'USD';
            $fields[] = ['name' => '💰 Total', 'value' => number_format($total / 100, 2) . ' ' . $currency, 'inline' => true];
        }

        return [
            'embeds' => [
                [
                    'title' => '⚡ ' . ucfirst(str_replace('.', ' ', $context->getEvent())),
                    'description' => $description,
                    'color' => 3447003, // Discord blue (#3498DB)
                    'fields' => $fields,
                    'footer' => ['text' => $context->get('workflow_name', 'Workflow')],
                    'timestamp' => (new \DateTimeImmutable())->format('c'),
                ],
            ],
        ];
    }

    private function buildSlackPayload(WorkflowContext $context, string $text): array
    {
        $subject = $context->getSubject();
        $fields = [];

        if (method_exists($subject, 'getNumber')) {
            $fields[] = ['type' => 'mrkdwn', 'text' => '*Order:* #' . $subject->getNumber()];
        }

        $customer = null;
        if (method_exists($subject, 'getCustomer') && $subject->getCustomer() !== null) {
            $customer = $subject->getCustomer();
        } elseif (method_exists($subject, 'getEmail')) {
            $customer = $subject;
        }

        if ($customer !== null && method_exists($customer, 'getEmail')) {
            $fields[] = ['type' => 'mrkdwn', 'text' => '*Customer:* ' . $customer->getEmail()];
        }

        return [
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => ['type' => 'plain_text', 'text' => '⚡ ' . ucfirst(str_replace('.', ' ', $context->getEvent()))],
                ],
                [
                    'type' => 'section',
                    'text' => ['type' => 'mrkdwn', 'text' => $text],
                ],
                [
                    'type' => 'section',
                    'fields' => $fields,
                ],
                [
                    'type' => 'context',
                    'elements' => [
                        ['type' => 'mrkdwn', 'text' => $context->get('workflow_name', 'Workflow') . ' • ' . $context->getChannel()],
                    ],
                ],
            ],
        ];
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
            $replacements['{order_number}'] = '#' . $subject->getNumber();
        }

        $customer = null;
        if (method_exists($subject, 'getCustomer') && $subject->getCustomer() !== null) {
            $customer = $subject->getCustomer();
        } elseif (method_exists($subject, 'getEmail')) {
            $customer = $subject;
        }

        if ($customer !== null) {
            $email = method_exists($customer, 'getEmail') ? ($customer->getEmail() ?? '') : '';
            $name = method_exists($customer, 'getFullName') ? trim($customer->getFullName()) : '';
            if ($name === '') {
                $firstName = method_exists($customer, 'getFirstName') ? ($customer->getFirstName() ?? '') : '';
                $lastName = method_exists($customer, 'getLastName') ? ($customer->getLastName() ?? '') : '';
                $name = trim($firstName . ' ' . $lastName);
            }
            if ($name === '') {
                $name = $email;
            }
            $replacements['{customer_email}'] = $email;
            $replacements['{customer_name}'] = $name;
        } else {
            $replacements['{customer_email}'] = '';
            $replacements['{customer_name}'] = '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
