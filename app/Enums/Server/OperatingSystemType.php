<?php

namespace App\Enums\Server;

enum OperatingSystemType: string
{
    /**
     * Unspecified OS
     */
    case OTHER = 'other';

    /**
     * Microsoft Windows XP
     */
    case WINDOWS_XP = 'wxp';

    /**
     * Microsoft Windows 2000
     */
    case WINDOWS_2000 = 'w2k';

    /**
     * Microsoft Windows 2003
     */
    case WINDOWS_2003 = 'w2k3';

    /**
     * Microsoft Windows 2008
     */
    case WINDOWS_2008 = 'w2k8';

    /**
     * Microsoft Windows Vista
     */
    case WINDOWS_VISTA = 'wvista';

    /**
     * Microsoft Windows 7
     */
    case WINDOWS_7 = 'win7';
    /**
     * Microsoft Windows 8/2012/2012r2
     */
    case WINDOWS_8 = 'win8';

    /**
     * Microsoft Windows 10/2016/2019
     */
    case WINDOWS_10 = 'win10';

    /**
     * Microsoft Windows 11/2022/2025
     */
    case WINDOWS_11 = 'win11';

    /**
     * Linux 2.4 Kernel
     */
    case LINUX_24 = 'l24';

    /**
     * Linux 2.6 - 6.X Kernel
     */
    case LINXUX_26 = 'l26';

    /**
     * Solaris/OpenSolaris/OpenIndiania kernel
     */
    case SOLARIS = 'solaris';

    /**
     * In the event that the OS is not listed in the above options, this will be used.
     * This may be an indicator that this enum class should be updated.
     */
    case UNKNOWN = 'unknown';

    // write a fromRaw method that takes a string and returns the corresponding enum value
    public static function fromRaw(string $raw): self
    {
        return match ($raw) {
            'other' => self::OTHER,
            'wxp' => self::WINDOWS_XP,
            'w2k' => self::WINDOWS_2000,
            'w2k3' => self::WINDOWS_2003,
            'w2k8' => self::WINDOWS_2008,
            'wvista' => self::WINDOWS_VISTA,
            'win7' => self::WINDOWS_7,
            'win8' => self::WINDOWS_8,
            'win10' => self::WINDOWS_10,
            'win11' => self::WINDOWS_11,
            'l24' => self::LINUX_24,
            'l26' => self::LINXUX_26,
            'solaris' => self::SOLARIS,
            default => self::UNKNOWN,
        };
    }
}
