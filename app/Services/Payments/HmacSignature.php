<?php

namespace App\Services\Payments;

/**
 * Stripe-style signed payloads: header "t=<unix>,v1=<hex hmac>", where the HMAC is
 * SHA-256 over "<t>.<raw body>". Used by both the Stripe and mock gateways, so the
 * mock exercises the same verification code path.
 */
final class HmacSignature
{
    public static function sign(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
    }

    public static function verify(string $payload, ?string $header, ?string $secret, int $toleranceSeconds = 300): void
    {
        if (! $secret) {
            throw new InvalidWebhookSignature('Webhook secret is not configured.');
        }
        if (! $header) {
            throw new InvalidWebhookSignature('Missing signature header.');
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($k === 't') {
                $timestamp = ctype_digit((string) $v) ? (int) $v : null;
            } elseif ($k === 'v1' && $v) {
                $signatures[] = $v;
            }
        }

        if (! $timestamp || ! $signatures) {
            throw new InvalidWebhookSignature('Malformed signature header.');
        }
        if (abs(time() - $timestamp) > $toleranceSeconds) {
            throw new InvalidWebhookSignature('Signature timestamp outside tolerance (possible replay).');
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return;
            }
        }

        throw new InvalidWebhookSignature('Signature mismatch.');
    }
}
