<?php

return [
    'power_actions' => [
        'start' => 'Start',
        'restart' => 'Restart',
        'kill' => 'Kill',
        'shutdown' => 'Shut Down',
    ],
    'kill_confirmation' => [
        'title' => 'Kill :name?',
        'description' => 'Killing this server cuts its power immediately, so anything it hasn\'t written to disk is lost. To stop it cleanly, shut it down instead.',
    ],
    'notices' => [
        'power_action_sent_success' => 'Power action sent.',
        'power_action_sent_fail' => 'Couldn\'t send the power action. Try again.',
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
    'poll_status_error' => 'Couldn\'t get the server\'s status. Retrying in 5 seconds...',

    'terminal' => [
        'title' => 'Terminal',
        'novnc_description' => 'Best for compatibility but lacks features and performance.',
        'xtermjs_description' => 'Best for performance but doesn\'t work for every operating system.',
    ],

    'server_config' => [
        'title' => 'Configure This Server',
        'description' => 'Edit this server\'s build settings in the admin area.',
        'configure_server' => 'Configure Server',
    ],
];
