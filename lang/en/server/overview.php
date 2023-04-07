<?php

return [
    'power_actions' => [
      'start' => 'Start',
      'restart' => 'Restart',
      'kill' => 'Kill',
      'shutdown' => 'Shutdown',
    ],
    'state' => 'State',
    'states' => [
        'stopped' => 'Stopped',
        'running' => 'Running',
        'stopping' => 'Stopping',
        'starting' => 'Starting',
        'shutting_down' => 'Shutting Down',
    ],
    'uptime' => 'Uptime',

    'bandwidth_usage' => 'Bandwidth Usage',

    'terminal' => [
        'title' => 'Terminal',
        'description' => 'Remotely manage your server from the web.',
        'novnc_description' => 'Best for compatibility but lacks features and performance.',
        'xtermjs_description' => 'Best for performance but doesn\'t work for every operating system.',
    ],

    'server_config' => [
        'title' => 'Configure This Server',
        'description' => 'You are an administrator! You can click below to visit this server\'s build configuration and make edits.',
        'configure_server' => 'Configure Server'
    ],
];