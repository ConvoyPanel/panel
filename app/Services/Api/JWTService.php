<?php

namespace Convoy\Services\Api;

use Carbon\CarbonImmutable;
use Convoy\Exceptions\Service\Api\InvalidJWTException;
use Convoy\Extensions\Lcobucci\JWT\Validation\Clock;
use Convoy\Models\User;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;

class JWTService
{
    private array $claims = [];

    private ?User $user = null;

    private ?\DateTimeImmutable $expiresAt;

    private ?string $subject = null;

    /**
     * Set the claims to include in this JWT.
     */
    public function setClaims(array $claims): self
    {
        $this->claims = $claims;

        return $this;
    }

    /**
     * Attaches a user to the JWT being created and will automatically inject the
     * "user_uuid" key into the final claims array with the user's UUID.
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setExpiresAt(\DateTimeImmutable $date): self
    {
        $this->expiresAt = $date;

        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param string $key
     * @param string $permittedFor A connection address
     * @param string|null $identifiedBy
     * @param string $algorithm
     * @return Plain
     */
    public function handle(string $key, string $permittedFor, ?string $identifiedBy, string $algorithm = 'sha256'): Plain
    {
        $identifier = hash($algorithm, $identifiedBy);
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($key));

        $builder = $config->builder()
            ->issuedBy(config('app.url'))
            ->permittedFor($permittedFor)
            ->identifiedBy($identifier)
            ->withHeader('jti', $identifier)
            ->issuedAt(CarbonImmutable::now())
            ->canOnlyBeUsedAfter(CarbonImmutable::now()->subMinutes(5));

        if ($this->expiresAt) {
            $builder = $builder->expiresAt($this->expiresAt);
        }

        if (! empty($this->subject)) {
            $builder = $builder->relatedTo($this->subject)->withHeader('sub', $this->subject);
        }

        foreach ($this->claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        if (! is_null($this->user)) {
            $builder = $builder
                ->withClaim('user_uuid', $this->user->uuid);
        }

        return $builder
            ->withClaim('unique_id', Str::random())
            ->getToken($config->signer(), $config->signingKey());
    }

    public function decode(string $key, string $token): UnencryptedToken
    {
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($key));

        try {
            $parsedToken = $config->parser()->parse($token);
        } catch (CannotDecodeContent|InvalidTokenStructure|UnsupportedHeaderFound $exception) {
            throw new InvalidJWTException($exception);
        }

        assert($parsedToken instanceof UnencryptedToken);

        if (! $config->validator()->validate($parsedToken, new StrictValidAt(new Clock))) {
            throw new InvalidJWTException;
        }

        return $parsedToken;
    }
}
