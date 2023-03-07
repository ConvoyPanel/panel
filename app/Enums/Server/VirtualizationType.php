<?php

namespace Convoy\Enums\Server;

enum VirtualizationType: string
{
    case KVM = 'kvm';
    case LXC = 'lxc';
}
