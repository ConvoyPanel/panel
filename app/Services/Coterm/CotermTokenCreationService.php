<?php

namespace Convoy\Services\Coterm;

use Convoy\Models\Coterm;
use Illuminate\Support\Str;

/**
 * This class is meant for generating the token for Coterm in the database so that JWT JTIs can be created.
 */
class CotermTokenCreationService
{
    /**
     * @return array{
     *       token: string,
     *       token_id: string
     * }
     */
    public function handle(): array
    {
        return [
            'token' => Str::random(Coterm::COTERM_TOKEN_LENGTH),
            'token_id' => Str::random(Coterm::COTERM_TOKEN_ID_LENGTH),
        ];
    }
}
