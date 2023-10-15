<?php

return [
    'power_actions' => [
        'start' => 'Start',
        'restart' => 'Uruchom ponownie',
        'kill' => 'Wymuś zatrzymanie',
        'shutdown' => 'Wyłącz',
    ],
    'notices' => [
        'power_action_sent_success' => 'Pomyślnie wysłano akcje. Przetwarzanie może chwilę potrwać.',
        'power_action_sent_fail' => 'Nie udało się wysłać zapytania.',
    ],

    'state' => 'Stan',
    'states' => [
        'stopped' => 'Wyłączony',
        'running' => 'Włączony',
        'stopping' => 'Zatrzymywanie',
        'starting' => 'Uruchamianie',
        'shutting_down' => 'Wyłączanie',
    ],
    'uptime' => 'Czas działania',
    'poll_status_error' => 'Nie udało się sprawdzić stanu serwera. Ponowna próba za 5 sekund...',

    'terminal' => [
        'title' => 'Zdalna konsola',
        'description' => 'Zdalne zarządzanie serwerem z poziomu strony.',
        'novnc_description' => 'Najlepsza pod względem kompatybilności, ale brakuje jej funkcji i wydajności.',
        'xtermjs_description' => 'Najlepsza pod względem wydajności, ale nie działa z każdym systemem.',
    ],

    'server_config' => [
        'title' => 'Konfiguracja tego serwera',
        'description' => 'Jesteś administratorem! Możesz kliknąć poniżej, aby odwiedzić konfigurację tego serwera i wprowadzić zmiany.',
        'configure_server' => 'Skonfiguruj serwer',
    ],
];
