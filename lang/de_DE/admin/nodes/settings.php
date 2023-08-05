<?php

return [
    'node_info' => [
        'title' => 'Nodeinformation',
    ],
    'coterm' => [
        'title' => 'Coterm',
        'description' => 'Mit Coterm kannst Du die IP deiner Node vor Benutzern verbergen, während diese auf die Webkonsole zugreifen.',
        'enable' => 'Coterm aktivieren',
        'tls' => 'SSL/TLS aktiviert',
        'token_created' => [
            'title' => 'Token erstellt',
            'description' => 'Hier ist Dein neu erstellter Token. Bitte notiere ihn Dir, da dieser nie wieder angezeigt werden kann.',
            'action' => 'Okay, ich habe es verstanden'
        ],
        'reset' => [
            'title' => 'Token zurücksetzen?',
            'description' => 'Bist Du sicher, dass Du den Coterm-Token dieser Node zurücksetzen möchtest? Alle Coterm-Instanzen, die diesen Token verwenden, werden nicht mehr funktionieren.',
            'action' => 'Token zurücksetzen',
        ]
    ]
];
