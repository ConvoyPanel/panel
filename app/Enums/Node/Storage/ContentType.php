<?php

namespace Convoy\Enums\Node\Storage;

enum ContentType: string
{
    case KVM_IMAGES = 'images';
    case LXC_DATA = 'rootdir';
    case LXC_TEMPLATES = 'vztmpl';
    case BACKUPS = 'backup';
    case ISO = 'iso';
    case SNIPPETS = 'snippets';
}
