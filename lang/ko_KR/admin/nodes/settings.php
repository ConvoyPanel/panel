<?php

return [
    'node_info' => [
        'title' => 'Node Information',
    ],
    'coterm' => [
        'title' => 'Coterm',
        'description' => 'Coterm lets you hide your node\'s origin from your users while they are accessing the web console.',
        'enable' => 'Enable Coterm',
        'tls' => 'TLS/SSL Enabled',
        'token_created' => [
            'title' => 'Token Created',
            'description' => 'Here is your newly created token. Please take note of the token\'s value as this is the only and
            last time you will see it.',
            'action' => 'Okay, I got it'
        ],
        'reset' => [
            'title' => 'Reset Token?',
            'description' => 'Are you sure you want to reset this node\'s Coterm token? Any Coterm instances that are relying on this token will fail.',
            'action' => 'Reset Token',
        ]
    ]
];
