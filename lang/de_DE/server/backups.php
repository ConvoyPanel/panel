<?php

return [
    'create_backup' => 'Backup erstellen',
    'empty_state' => 'Es gibt keine Backups',
    'counter_tooltip' => 'Du hast :count von :max Backups erstellt',
    'create_modal' => [
        'title' => 'Ein Backup erstellen',
        'description' => 'Creating a backup will take a copy of your server files. This can take a while depending on the size of your server.',
        'compression_type' => 'Compression Type',
        'mode' => 'Typ',
        'modes' => [
            'snapshot' => 'Snapshot',
            'suspend' => 'Sperren',
            'kill' => 'Ausschalten',
        ],
    ],
    'delete_modal' => [
        'title' => ':name Löschen',
        'description' => 'Bist Du sicher, dass Du dieses Backup löschen möchtest?',
    ],
    'restore_modal' => [
        'title' => 'Wiederherstellen von :name',
        'description' => 'Bist Du sicher, dass Du dieses Backup wiederherstellen möchtest?',
    ],
    'notices' => [
        'backup_deleted' => ':name wurde gelöscht ',
        'backup_restored' => 'Beginne Wiederherstellung aus :name',
    ],
];
