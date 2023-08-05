<?php

return [
    'server_info' => [
        'title' => 'Serverinformation',
        'statuses' => [
            'ready' => 'Bereit',
            'installing' => 'Installation in Arbeit',
            'install_failed' => 'Aktuelle Installation Fehlgeschlagen',
            'suspended' => 'Gesperrt',
            'restoring_backup' => 'Wiederherstellung aus Backup',
            'restoring_snapshot' => 'Wiederherstellung aus Snapshot',
        ]
    ],
    'suspension' => [
        'title' => 'Sperrung',
        'description' => 'Schalte den Sperrungsstatus um.',
        'statuses' => [
            'suspended' => 'Dieser Server ist gesperrt.',
            'not_suspended' => 'Dieser Server ist nicht gesperrt.'
        ],
        'suspend' => 'Sperren',
        'unsuspend' => 'Entsperren',
    ],
    'deletion' => [
        'title' => 'Server löschen',
        'description' => 'Dieser Server wird aus Convoy gelöscht. Backups und andere zugewiesene Daten werden vernichtet. Du kannst jedoch den Haken unten setzen, um die virtuelle Maschine und Daten auf der Proxmox-Node zu behalten.',
        'deleting_status' => 'Dieser Server wird aktuell gelöscht.',
        'no_purge' => 'VM und zugewiesene Dateien nicht löschen',
        'confirmation' => [
            'title' => ':name Löschen',
            'description' => 'Bist Du sicher, dass Du :name löschen möchtest?'
        ]
    ],
    'build' => [
        'title' => 'Server Build',

    ]
];