<?php
namespace App\Services;

class WebhookService
{
    function signFields(array $fields, string $secretKey): string {
    $signed = [];
    $allowedFields = [
        'order_amount', 'merchant', 'currency', 'operation',
        'order_description', 'order_invoice_number', 'customer_id',
        'payment_method', 'success_url', 'error_url', 'cancel_url',
    ];

    foreach ($allowedFields as $field) {
        if (! isset($fields[$field])) continue;
        $signed[] = $field . '=' . $fields[$field];
    }

    return base64_encode(hash_hmac('sha256', implode(',', $signed), $secretKey, true));
    }
}