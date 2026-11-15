<?php

return [
    'telemetry' => [
        'title' => 'Telemetry',
        'description' => 'Once a day, the panel sends an anonymous usage report to the Convoy console: counts of accounts, nodes, servers and page views, which features are on, and version numbers. It never includes names, email or IP addresses, hostnames or keys. Turning it off deletes the reports already sent.',
        'switch' => 'Send a Daily Usage Report',
        'disabled_by' => 'Turned off by the :variable environment variable.',
        'enabled' => 'Telemetry is on.',
        'disabled' => 'Telemetry is off.',
        'view_report' => 'View Report',
        'report_title' => 'Telemetry Report',
        'report_description' => 'Exactly what the next report sends.',
        'close' => 'Close',
    ],
];
