<?php

return [
    'ip_allocation' => [
        'empty_state' => 'There are no addresses associated with this server.',
    ],
    'display_info' => [
        'title' => 'Server Name',
    ],
    'reinstall' => [
        'title' => 'Reinstall Server',
        'description' => 'Start your server on a fresh slate.',
        'start_server_after_installing' => 'Start Server After Completing Installation',
        'modal' => [
            'title' => 'Confirm Reinstallation',
            'description' => 'Are you sure you want to reinstall this server? All data will be lost.',
        ],
    ],
    'retry_install' => [
        'failed_title' => 'Install failed',
        'failed_message' => 'Your server failed to install. Pick a template to try again, or contact your administrator if it keeps failing.',
        'title' => 'Retry Installation',
        'description' => 'Installs the server again from the template you pick, with a new OS password.',
        'button' => 'Retry Installation',
    ],
    'isos' => [
        'title' => 'Mountable ISOs',
        'empty' => 'There are no ISOs',
    ],
    'device_config' => [
        'title' => 'Device Configuration',
        'current' => 'Current Boot Order (the highest will be used first)',
        'unused' => 'Unused Devices',
        'unused_empty' => 'There are no unused devices.',
        'no_boot_device_warning' => 'No boot device has been configured. Your VM will not start.',
    ],
    'nameservers' => [
        'title' => 'Nameservers',
        'nameserver' => 'Nameserver :index',
        'add' => 'New Nameserver',
    ],
    'auth' => [
        'title' => 'Authentication',
    ],
    'hardware' => [
        'bandwidth_used' => 'Bandwidth Used',
        'bandwidth_alloted' => 'Bandwidth Alloted',
    ],
];
