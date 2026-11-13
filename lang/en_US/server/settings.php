<?php

return [
    'ip_allocation' => [
        'empty_state' => 'None',
    ],
    'display_info' => [
        'title' => 'Server Name',
    ],
    'reinstall' => [
        'title' => 'Reinstall Server',
        'description' => 'Erases this server\'s disk and installs the template you pick.',
        'start_server_after_installing' => 'Start Server After Installing',
        'modal' => [
            'title' => 'Reinstall Server?',
            'description' => 'Reinstalling this server erases all of its data.',
        ],
    ],
    'retry_install' => [
        'failed_title' => 'Install Failed',
        'failed_message' => 'Pick a template to try again, or contact your administrator if it keeps failing.',
        'title' => 'Retry Installation',
        'description' => 'Installs the server again from the template you pick, with a new OS password.',
        'button' => 'Retry Installation',
    ],
    'isos' => [
        'title' => 'Mountable ISOs',
        'empty' => 'No ISOs',
    ],
    'device_config' => [
        'title' => 'Device Configuration',
        'current' => 'Boot Order',
        'unused' => 'Unused Devices',
        'unused_empty' => 'None',
        'no_boot_device_warning' => 'This server won\'t start without a boot device. Add one from Unused Devices.',
        'remove_device' => 'Remove :device from the boot order',
        'add_device' => 'Add :device to the boot order',
        'reorder_device' => 'Drag to reorder :device',
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
        'bandwidth_used' => 'Bandwidth Usage',
        'bandwidth_alloted' => 'Bandwidth Limit',
    ],
];
