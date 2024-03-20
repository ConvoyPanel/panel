<?php

return [
    'ip_allocation' => [
        'empty_state' => 'Z tym serwerem nie są powiązane żadne adresy.',
    ],
    'display_info' => [
        'title' => 'Nazwa serwera',
    ],
    'reinstall' => [
        'title' => 'Przeinstaluj serwer',
        'description' => 'Uruchom swój serwer na świeżo.',
        'start_server_after_installing' => 'Uruchom serwer po ukończeniu instalacji',
        'modal' => [
            'title' => 'Potwierdź ponowną instalację',
            'description' => 'Czy na pewno chcesz ponownie przeinstalować ten serwer? Wszystkie dane zostaną utracone.',
        ],
    ],
    'isos' => [
        'title' => 'Montowalne ISO',
        'empty' => 'Nie ma żadnych ISO',
    ],
    'device_config' => [
        'title' => 'Konfiguracja urządzenia',
        'current' => 'Bieżąca kolejność rozruchu (najwyższa zostanie użyta jako pierwsza)',
        'unused' => 'Nieużywane urządzenia',
        'unused_empty' => 'Nie ma nieużywanych urządzeń.',
        'no_boot_device_warning' => 'Brak opcji rozruchu. Maszyna się nie uruchomi.',
    ],
    'nameservers' => [
        'title' => 'Serwery DNS',
        'nameserver' => 'Serwer :index',
        'add' => 'Nowy serwer',
    ],
    'auth' => [
        'title' => 'Uwierzytelnianie',
    ],
    'hardware' => [
        'bandwidth_used' => 'Wykorzystana sieć',
        'bandwidth_alloted' => 'Przydzielona sieć',
    ],
];
