<?php

namespace App\Enums\Webhook;

enum WebhookDeliveryStatus: string
{
    /** Queued, or failed at least once and waiting for its next attempt. */
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    /** Every attempt failed. A redelivery puts it back to pending. */
    case FAILED = 'failed';
}
