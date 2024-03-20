<?php

return [
    'power_actions' => [
        'start' => 'Starten',
        'restart' => 'Neustarten',
        'kill' => 'Aus',
        'shutdown' => 'Herunterfahren',
    ],
    'notices' => [
        'power_action_sent_success' => 'Der Befehl wurde erfolgreich gesendet. Es kann einen Moment dauern, bis er verarbeitet ist.',
        'power_action_sent_fail' => 'Befehl fehlgeschlagen.',
    ],

    'state' => 'Status',
    'states' => [
        'stopped' => 'Gestoppt',
        'running' => 'Läuft',
        'stopping' => 'Stoppt',
        'starting' => 'Startet',
        'shutting_down' => 'Fährt herunter',
    ],
    'uptime' => 'Betriebszeit',
    'poll_status_error' => 'Der Serverstatus konnte nicht abgefragt werden. Versuche in 5 Sekunden erneut...',

    'terminal' => [
        'title' => 'Konsole',
        'description' => 'Verwalte Deinen Server per Fernzugriff.',
        'novnc_description' => 'Am besten für Kompatibilität geeignet, aber Funktionen und Leistung sind eventuell eingeschränkt.',
        'xtermjs_description' => 'Am besten für Leistung geeignet, aber nicht von jedem Betriebssystem unterstützt.',
    ],

    'server_config' => [
        'title' => 'Server konfigurieren',
        'description' => 'Du bist ein Administrator! Du kannst mit einem Klick unten die Konfiguration des Servers ansehen und bearbeiten.',
        'configure_server' => 'Server konfigurieren',
    ],
];
