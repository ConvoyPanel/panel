<?php

namespace App\Data\Coterm;

use App\Models\Coterm;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapInputName(SnakeCaseMapper::class)]
class CotermData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isTlsEnabled,
        public string $fqdn,
        public int $port,
        public int $nodesCount,
        public Optional|string $tokenId,
        public Optional|string $token,
    ) {}

    public static function fromModel(Coterm $coterm, bool $includeToken = false): self
    {
        return new self(
            id: (int) $coterm->id,
            name: $coterm->name,
            isTlsEnabled: (bool) $coterm->is_tls_enabled,
            fqdn: $coterm->fqdn,
            port: (int) $coterm->port,
            nodesCount: (int) ($coterm->nodes_count ?? 0),
            tokenId: $includeToken ? $coterm->token_id : Optional::create(),
            token: $includeToken ? $coterm->token : Optional::create(),
        );
    }
}
