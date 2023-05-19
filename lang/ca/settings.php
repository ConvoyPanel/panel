<?php

return [
    'server_info' => [
        'title' => 'Server Information',
        'statuses' => [
            'ready' => 'Ready',
            'installing' => 'Installation In Progress',
            'install_failed' => 'Recent Installation Failed',
            'suspended' => 'Suspended',
            'restoring_backup' => 'Restoring From a Backup',
            'restoring_snapshot' => 'Restoring From a Snapshot',
        ]
    ],
    'suspension' => [
        'title' => 'Suspension',
        'description' => 'Toggle the suspension status of the server.',
        'statuses' => [
            'suspended' => 'This server is suspended.',
            'not_suspended' => 'This server isn\'t suspended.'
        ],
        'suspend' => 'Suspend',
        'unsuspend' => 'Unsuspend',
    ],
    'deletion' => [
        'title' => 'Delete Server',
        'description' => 'The server will be deleted from Convoy. Backups and other associated data will be
                                destroyed. However, you can tick the checkbox below to keep the virtual machine and data
                                on the Proxmox node.',
        'deleting_status' => 'This server is currently being deleted.',
        'no_purge' => 'Do not purge VM and related files',
        'confirmation' => [
            'title' => 'Delete :name',
            'description' => 'Are you sure you want to delete :name?'
        ]
    ],
    'build' => [
        'title' => 'Server Build',

    ]
];