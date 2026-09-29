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
        'description' => 'Delete removes the server completely: its backups, its virtual machine on the Proxmox node, and its entry in Convoy. If the virtual machine is already gone from the node, only the Convoy entry is removed. Disconnect removes the server from Convoy only, and leaves the virtual machine and its backups on the node untouched.',
        'deleting_status' => 'This server is currently being deleted.',
        'failed_status' => 'The last attempt to delete this server failed. You can delete it again, or disconnect it to remove it from Convoy only.',
        'disconnect' => 'Disconnect',
        'confirmation' => [
            'title' => 'Delete :name',
            'description' => 'Are you sure you want to delete :name? Its virtual machine and backups will be destroyed on the Proxmox node.'
        ],
        'disconnect_confirmation' => [
            'title' => 'Disconnect :name',
            'description' => 'Remove :name from Convoy? Its virtual machine and backups stay on the Proxmox node, and Convoy will no longer manage them.'
        ],
    ],
    'build' => [
        'title' => 'Server Build',

    ]
];