<?php

return [
    'node_info' => [
        'title' => 'Nodeinformation',
    ],
    'coterm' => [
        'title' => 'Coterm',
        'description' => 'Mit Coterm kannst du die IP deiner Node vor Benutzern verbergen, während diese auf die Webkonsole zugreifen.',
        'enable' => 'Coterm aktivieren',
        'tls' => 'SSL/TLS aktiviert',
        'token_created' => [
            'title' => 'Token erstellt',
            'description' => 'Hier ist dein neu erstellter Token. Bitte notiere ihn dir, da dieser nie wieder angezeigt werden kann.',
            'action' => 'Okay, ich habe es verstanden'
        ],
        'reset' => [
            'title' => 'Token zurücksetzen?',
            'description' => 'Bist du sicher, dass du den Coterm-Token dieser Node zurücksetzen möchtest? Alle Coterm-Instanzen, die diesen Token verwenden, werden nicht mehr funktionieren.',
            'action' => 'Token zurücksetzen',
        ]
    ]
];
