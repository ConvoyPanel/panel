<?php

return [
    'create_backup' => 'Create Backup',
    'empty_state' => 'No backups',
    'counter_tooltip' => 'You\'ve made :count out of :max backups',
    'create_modal' => [
        'title' => 'Create Backup',
        'description' => 'Large servers can take a while to back up.',
        'compression_type' => 'Compression Type',
        'mode' => 'Mode',
        'modes' => [
            'snapshot' => 'Snapshot',
            'suspend' => 'Suspend',
            'kill' => 'Kill',
        ],
    ],
    'delete_modal' => [
        'title' => 'Delete :name?',
        'description' => 'This can\'t be undone.',
    ],
    'restore_modal' => [
        'title' => 'Restore From :name?',
        'description' => 'Restoring this backup replaces everything on this server, so anything saved since it was made is lost.',
    ],
    'notices' => [
        'backup_deleted' => 'Deleted :name',
        'backup_restored' => 'Restoring from :name',
    ],
];
