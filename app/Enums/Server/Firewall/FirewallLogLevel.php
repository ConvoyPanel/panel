<?php

namespace App\Enums\Server\Firewall;

/**
 * Syslog severity a firewall rule or direction logs at.
 *
 * `NoLog` is Proxmox's own opt-out value rather than an absent field, so it is
 * a real case here instead of being modelled as null.
 */
enum FirewallLogLevel: string
{
    case Emergency = 'emerg';
    case Alert = 'alert';
    case Critical = 'crit';
    case Error = 'err';
    case Warning = 'warning';
    case Notice = 'notice';
    case Info = 'info';
    case Debug = 'debug';
    case NoLog = 'nolog';
}
