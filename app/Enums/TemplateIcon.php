<?php

namespace App\Enums;

enum TemplateIcon: string
{
    case UBUNTU = 'ubuntu';
    case DEBIAN = 'debian';
    case CENTOS = 'centos';
    case FEDORA = 'fedora';
    case ROCKY_LINUX = 'rocky_linux';
    case ALMALINUX = 'almalinux';
    case WINDOWS = 'windows';
    case ALPINE_LINUX = 'alpine_linux';
    case ARCH_LINUX = 'arch_linux';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
