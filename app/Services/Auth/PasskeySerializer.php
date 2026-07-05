<?php

namespace App\Services\Auth;

use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

class PasskeySerializer
{
    public static function make(): PasskeySerializer
    {
        $attestationStatementSupportManager = AttestationStatementSupportManager::create();

        $serializer = (new WebauthnSerializerFactory($attestationStatementSupportManager))
            ->create();

        return new self($serializer);
    }

    public function __construct(
        protected SerializerInterface $serializer,
    ) {}

    public function toJson(mixed $value): string
    {
        return $this->serializer->serialize($value, 'json');
    }

    /**
     * @param  class-string  $desiredClass
     */
    public function fromJson(string $value, string $desiredClass): mixed
    {
        return $this
            ->serializer
            ->deserialize($value, $desiredClass, 'json');
    }
}
