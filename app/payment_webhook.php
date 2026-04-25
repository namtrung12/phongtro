<?php
declare(strict_types=1);

require_once __DIR__ . '/repositories.php';

function webhookJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function webhookHeader(string $name): string
{
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    if (!empty($_SERVER[$key])) {
        return (string)$_SERVER[$key];
    }
    $redirectKey = 'REDIRECT_' . $key;
    if (!empty($_SERVER[$redirectKey])) {
        return (string)$_SERVER[$redirectKey];
    }
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $header => $value) {
            if (strcasecmp((string)$header, $name) === 0) {
                return (string)$value;
            }
        }
    }
    return '';
}

function authorizeSepayWebhook(): void
{
    $apiKey = trim((string)env('SEPAY_WEBHOOK_API_KEY', ''));
    if ($apiKey === '') {
        return;
    }

    $authorization = webhookHeader('Authorization');
    $expected = 'Apikey ' . $apiKey;
    if (!hash_equals($expected, $authorization)) {
        webhookJson(['success' => false, 'message' => 'unauthorized'], 401);
    }
}

function parseWebhookInput(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    $input['_raw'] = $raw;
    return $input;
}

function normalizeWebhookAmount($value): int
{
    if (is_int($value)) {
        return $value;
    }
    if (is_float($value)) {
        return (int)round($value);
    }
    return (int)preg_replace('/[^\d]/', '', (string)$value);
}

function extractWebhookPaymentCode(array $input): string
{
    $candidates = [
        $input['code'] ?? '',
        $input['payment_code'] ?? '',
        $input['content'] ?? '',
        $input['description'] ?? '',
        $input['note'] ?? '',
        $input['comment'] ?? '',
    ];

    foreach ($candidates as $value) {
        $text = strtoupper(trim((string)$value));
        if ($text === '') {
            continue;
        }
        if (preg_match('/\b(LEAD\d+)\b/i', $text, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/\b([A-Z0-9]{5}\d{3})\b/i', $text, $m)) {
            return strtoupper($m[1]);
        }
    }

    return '';
}

function handleSepayPaymentWebhook(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        transactionLog('payment_webhook_rejected', [
            'status' => 'method_not_allowed',
            'entity_type' => 'payment',
            'entity_id' => '',
        ]);
        webhookJson(['success' => false, 'message' => 'method_not_allowed'], 405);
    }

    authorizeSepayWebhook();
    $input = parseWebhookInput();

    $transferType = strtolower(trim((string)($input['transferType'] ?? $input['transfer_type'] ?? 'in')));
    if ($transferType !== '' && $transferType !== 'in') {
        webhookJson(['success' => true, 'message' => 'ignored_outgoing']);
    }

    $code = extractWebhookPaymentCode($input);
    $amount = normalizeWebhookAmount($input['transferAmount'] ?? $input['amount'] ?? $input['transfer_amount'] ?? 0);
    $providerRef = (string)($input['referenceCode'] ?? $input['reference_code'] ?? $input['id'] ?? $input['transaction_id'] ?? 'sepay-webhook');
    $payloadHash = hash('sha256', (string)($input['_raw'] ?? ''));
    $eventKey = strtoupper(trim($providerRef)) . '|' . strtoupper(trim($code)) . '|' . (string)$amount;
    if (!acquireWebhookIdempotencyLock('sepay', $eventKey, $payloadHash)) {
        transactionLog('payment_webhook_duplicate', [
            'status' => 'ignored',
            'entity_type' => 'payment',
            'entity_id' => '',
            'reference_code' => $providerRef,
            'amount' => $amount,
        ]);
        webhookJson(['success' => true, 'message' => 'already_processed', 'code' => $code]);
    }

    if ($code === '' || $amount <= 0) {
        transactionLog('payment_webhook_invalid_payload', [
            'status' => 'invalid_payload',
            'entity_type' => 'payment',
            'entity_id' => '',
            'reference_code' => $providerRef,
            'amount' => $amount,
        ]);
        webhookJson([
            'success' => false,
            'message' => 'invalid_payload',
            'code' => $code,
            'amount' => $amount,
        ], 400);
    }

    $payment = findPaymentByCode($code);
    if (!$payment) {
        transactionLog('payment_webhook_payment_not_found', [
            'status' => 'not_found',
            'entity_type' => 'payment',
            'entity_id' => '',
            'reference_code' => $providerRef,
            'amount' => $amount,
        ]);
        webhookJson(['success' => false, 'message' => 'payment_not_found', 'code' => $code], 404);
    }

    if ((string)$payment['status'] === 'paid') {
        transactionLog('payment_webhook_already_paid', [
            'status' => 'paid',
            'entity_type' => 'payment',
            'entity_id' => (string)((int)($payment['id'] ?? 0)),
            'reference_code' => $providerRef,
            'amount' => $amount,
        ]);
        webhookJson(['success' => true, 'message' => 'already_paid', 'code' => $code]);
    }

    if (expirePaymentIfNeeded($payment)) {
        transactionLog('payment_webhook_expired', [
            'status' => 'expired',
            'entity_type' => 'payment',
            'entity_id' => (string)((int)($payment['id'] ?? 0)),
            'reference_code' => $providerRef,
            'amount' => $amount,
        ]);
        webhookJson(['success' => false, 'message' => 'payment_expired', 'code' => $code], 410);
    }

    if ((int)$payment['amount'] !== $amount) {
        transactionLog('payment_webhook_amount_mismatch', [
            'status' => 'amount_mismatch',
            'entity_type' => 'payment',
            'entity_id' => (string)((int)($payment['id'] ?? 0)),
            'reference_code' => $providerRef,
            'amount' => $amount,
            'expected_amount' => (int)$payment['amount'],
        ]);
        webhookJson([
            'success' => false,
            'message' => 'amount_mismatch',
            'code' => $code,
            'expected' => (int)$payment['amount'],
            'received' => $amount,
        ], 400);
    }

    if (markPaymentPaid((int)$payment['id'], $providerRef)) {
        openLeadByPayment((int)$payment['lead_id']);
        transactionLog('payment_paid_via_webhook', [
            'status' => 'paid',
            'entity_type' => 'payment',
            'entity_id' => (string)((int)($payment['id'] ?? 0)),
            'reference_code' => $providerRef,
            'amount' => $amount,
            'lead_id' => (int)($payment['lead_id'] ?? 0),
        ]);
        auditLog('lead.full_contact_unlocked', [
            'entity_type' => 'lead',
            'entity_id' => (string)((int)($payment['lead_id'] ?? 0)),
            'payment_id' => (int)($payment['id'] ?? 0),
            'reference_code' => $providerRef,
        ]);
        webhookJson(['success' => true, 'message' => 'paid', 'code' => $code]);
    }

    transactionLog('payment_webhook_update_failed', [
        'status' => 'update_failed',
        'entity_type' => 'payment',
        'entity_id' => (string)((int)($payment['id'] ?? 0)),
        'reference_code' => $providerRef,
        'amount' => $amount,
    ]);
    webhookJson(['success' => false, 'message' => 'update_failed', 'code' => $code], 500);
}
