<?php

namespace App\Data\Server\Adoption;

use App\Enums\Network\AddressVersion;
use App\Enums\Server\AddressAdoptionVerdict;
use Spatie\LaravelData\Data;

/**
 * One address read off a guest's `ipconfig0`, with what the panel would do
 * about it and why.
 */
class AdoptionAddressData extends Data
{
    public function __construct(
        public readonly string $ip,
        public readonly int $prefixLength,
        public readonly AddressVersion $version,
        public readonly ?string $gateway,
        public readonly AddressAdoptionVerdict $verdict,
        /** One line the operator can act on. Always present; the verdict alone is a word. */
        public readonly string $reason,
        /** The block it falls in, when exactly one reachable block covers it. */
        public readonly ?string $blockName,
        public readonly ?string $poolName,
        /** The server already holding it, for a conflict. */
        public readonly ?string $conflictingServerName,
    ) {}
}
