<?php

namespace App\Data\Server;

use App\Enums\Server\OveragePenaltyAction;
use Spatie\LaravelData\Data;

/**
 * A resolved (or configured) quota-overage penalty. `rate` is in bytes/s and is
 * only meaningful when `action` is THROTTLE. See docs/bandwidth-rate-limiting-plan.md §5.
 */
class OveragePenaltyData extends Data
{
    public function __construct(
        public OveragePenaltyAction $action,
        public ?int $rate = null,
    ) {}

    public static function throttle(int $rate): self
    {
        return new self(OveragePenaltyAction::THROTTLE, $rate);
    }

    public static function disconnect(): self
    {
        return new self(OveragePenaltyAction::DISCONNECT, null);
    }

    public function isThrottle(): bool
    {
        return $this->action === OveragePenaltyAction::THROTTLE;
    }

    public function isDisconnect(): bool
    {
        return $this->action === OveragePenaltyAction::DISCONNECT;
    }
}
