<?php

return [
    'create_backup' => 'Create Backup',
    'empty_state' => 'There are no backups',
    'counter_tooltip' => 'You\'ve made :count out of :max backups',
    'create_modal' => [
        'title' => 'Create a Backup',
        'description' => 'Creating a backup will take a copy of your server files. This can take a while depending on the size of your server.',
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
        'description' => 'Are you sure you want to delete this backup?',
    ],
    'restore_modal' => [
        'title' => 'Restore From :name?',
        'description' => 'Are you sure you want to restore from this backup?',
    ],
    'notices' => [
        'backup_deleted' => 'Deleted :name',
        'backup_restored' => 'Began restoring server from :name',
    ],
];
