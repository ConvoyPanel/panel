<?php

return [
    'power_actions' => [
      'start' => 'Start',
      'restart' => 'Restart',
      'kill' => 'Kill',
      'shutdown' => 'Shutdown',
    ],
    'notices' => [
        'power_action_sent_success' => 'Successfully sent power action. It may take a moment to process.',
        'power_action_sent_fail' => 'Failed to send power action.',
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
    'poll_status_error' => 'Failed to poll server status. Retrying in 5 seconds...',

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