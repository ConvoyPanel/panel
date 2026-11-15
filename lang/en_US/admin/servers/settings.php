<?php

return [
    'server_info' => [
        'title' => 'Server Information',
        'statuses' => [
            'ready' => 'Ready',
            'installing' => 'Installing',
            'install_failed' => 'Installation Failed',
            'suspended' => 'Suspended',
            'restoring_backup' => 'Restoring From a Backup',
            'restoring_snapshot' => 'Restoring From a Snapshot',
        ]
    ],
    'suspension' => [
        'title' => 'Suspension',
        'statuses' => [
            'suspended' => 'This server is suspended.',
            'not_suspended' => 'This server isn\'t suspended.'
        ],
        'suspend' => 'Suspend',
        'unsuspend' => 'Unsuspend',
        'confirmation' => [
            'title' => 'Suspend :name?',
            'description' => 'Suspending this server powers it off immediately without a clean shutdown, and its owner can\'t manage it until you unsuspend it.',
        ],
    ],
    'deletion' => [
        'title' => 'Delete Server',
        'description' => 'Delete removes this server from Convoy and deletes its virtual machine and backups on the Proxmox node. Disconnect only removes it from Convoy.',
        'deleting_status' => 'This server is currently being deleted.',
        'failed_status' => 'Deleting this server failed. Try again, or disconnect it to remove it from Convoy only.',
        'disconnect' => 'Disconnect',
        'confirmation' => [
            'title' => 'Delete :name?',
            'description' => 'Deleting this server also deletes its virtual machine and backups on the Proxmox node.'
        ],
        'disconnect_confirmation' => [
            'title' => 'Disconnect :name?',
            'description' => 'Disconnecting this server only removes it from Convoy and keeps its virtual machine and backups on the Proxmox node, but Convoy can no longer manage them.'
        ],
    ],
    'build' => [
        'title' => 'Server Build',

    ]
];