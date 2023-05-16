<?php

return [
    'create_server' => 'Create Server',
    'backup_limit' => 'Backup Limit',
    'bandwidth_limit' => 'Bandwidth Limit',
    'snapshots_limit' => 'Snapshots Limit',
    'limit_placeholder' => 'Leave blank for no limit',
    'should_create_vm' => 'Should Create VM',
    'start_server_after_installing' => 'Start Server After Completing Installation',
    'vmid_placeholder' => 'Leave blank for random VMID',
    'create_modal' => [
        'title' => 'Create a Server',
    ],
    'manage_server' => 'Manage Server',
    'server_build' => [
        'title' => 'Server Build',
    ],
    'server_info' => [
        'title' => 'Server Information',
    ],
    'suspension' => [
        'title' => 'Suspension',
        'description' => 'Toggle the suspension status of the server.',
        'not_suspended' => 'This server isn\'t suspended',
        'suspended' => 'This server is suspended',
    ],
    'delete_server' => [
        'title' => 'Delete Server',
        'description' => 'The server will be deleted from Convoy. Backups and other associated data will be destroyed. However, you can tick the checkbox below to keep the virtual machine and data on the Proxmox node.',
        'dont_purge' => 'Do not purge VM and related files',
    ],
];
