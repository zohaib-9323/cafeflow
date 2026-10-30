<?php

namespace App\Services\Payments;

/** A provider-neutral webhook event. */
final readonly class WebhookEvent
{
    public const SUCCEEDED = 'succeeded';

    public const FAILED = 'failed';

    public function __construct(
        public string $id,
        public string $type,
        public ?string $reference,
        public ?string $outcome,          // SUCCEEDED | FAILED | null for events we don't act on
        public ?string $failureReason = null,
        public array $meta = [],
    ) {}
}
