<?php

namespace App\Enums\Server;

enum HugePagesSetting: string
{
    case ANY = 'any';
    case SIZE_2MB = '2';
    case SIZE_1GB = '1024';

//    public static function fromString(?string $value): ?self
//    {
//        if ($value === null) {
//            return null;
//        }
//
//        return match ($value) {
//            'any' => self::ANY,
//            '2' => self::SIZE_2MB,
//            '1024' => self::SIZE_1GB,
//            default => throw new \InvalidArgumentException("Invalid huge pages setting: {$value}")
//        };
//    }
}
