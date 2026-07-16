<?php

namespace App\Services\Api;

use App\Exceptions\Service\Api\InvalidJWTException;
use App\Extensions\Lcobucci\JWT\Validation\Clock;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;

class JWTService
{
    /**
     * Mint a signed JWT.
     *
     * @param  string  $signingKey  HMAC-SHA256 signing key.
     * @param  string  $audience  Who the token is permitted for (a connection address).
     * @param  string  $identifier  Seed hashed into the token's unique id (jti).
     * @param  array<string, mixed>  $claims  Additional claims to embed.
     */
    public function issue(
        string $signingKey,
        string $audience,
        string $identifier,
        array $claims = [],
        ?DateTimeImmutable $expiresAt = null,
    ): Plain {
        $config = $this->configFor($signingKey);
        $now = CarbonImmutable::now();
        $jti = hash('sha256', $identifier);

        $builder = $config->builder()
            ->issuedBy(config('app.url'))
            ->permittedFor($audience)
            ->identifiedBy($jti)
            ->withHeader('jti', $jti)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now->subMinutes(5))
            ->withClaim('unique_id', Str::random());

        if ($expiresAt !== null) {
            $builder = $builder->expiresAt($expiresAt);
        }

        foreach ($claims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        $token = $builder->getToken($config->signer(), $config->signingKey());

        if (! $token instanceof Plain) {
            throw new \LogicException('Expected JWT builder to return a plain token.');
        }

        return $token;
    }

    public function decode(string $signingKey, string $token): UnencryptedToken
    {
        $config = $this->configFor($signingKey);

        try {
            $parsedToken = $config->parser()->parse($token);
        } catch (CannotDecodeContent|InvalidTokenStructure|UnsupportedHeaderFound $exception) {
            throw new InvalidJWTException($exception);
        }

        assert($parsedToken instanceof UnencryptedToken);

        // StrictValidAt alone only checks the token is well-formed and unexpired, which would
        // accept a forged token with arbitrary claims. SignedWith confirms it was signed with our key.
        if (! $config->validator()->validate(
            $parsedToken,
            new StrictValidAt(new Clock),
            new SignedWith($config->signer(), $config->signingKey()),
        )) {
            throw new InvalidJWTException;
        }

        return $parsedToken;
    }

    private function configFor(string $signingKey): Configuration
    {
        return Configuration::forSymmetricSigner(new Sha256, InMemory::plainText($signingKey));
    }
}
