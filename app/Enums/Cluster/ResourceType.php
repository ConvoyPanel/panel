<?php

namespace App\Enums\Cluster;

enum ResourceType: string
{
    case NODE = 'node';
    case STORAGE = 'storage';
    case POOL = 'pool';
    case QEMU = 'qemu';
    case LXC = 'lxc';
    case OPENVZ = 'openvz';
    case SDN = 'sdn';
}
