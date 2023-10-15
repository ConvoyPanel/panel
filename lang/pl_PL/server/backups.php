<?php

return [
    'create_backup' => 'Stwórz kopię',
    'empty_state' => 'Nie ma żadnych kopii',
    'counter_tooltip' => 'Wykonałeś :count z :max kopii zapasowych',
    'create_modal' => [
        'title' => 'Tworzenie kopii zapasowej',
        'description' => 'Utworzenie kopii zapasowej spowoduje wykonanie kopii plików serwera. Może to trochę potrwać w zależności od wielkości serwera.',
        'compression_type' => 'Typ kompresji',
        'mode' => 'Tryb',
        'modes' => [
            'snapshot' => 'Snapshot',
            'suspend' => 'Wyłączony',
            'kill' => 'Wymuszony',
        ],
    ],
    'delete_modal' => [
        'title' => 'Usunąć :name?',
        'description' => 'Czy na pewno chcesz usunąć kopię zapasową?',
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
