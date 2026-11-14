<?php

return [
    'telemetry' => [
        'title' => 'Telemetry',
        'description' => 'Once a day, the panel sends an anonymous usage report to the Convoy console: counts of accounts, nodes, servers and page views, which features are on, and version numbers. It never includes names, email or IP addresses, hostnames or keys. Turning it off deletes the reports already sent.',
        'switch' => 'Send a daily usage report',
        'disabled_by' => 'Turned off by the :variable environment variable.',
        'enabled' => 'Telemetry is on.',
        'disabled' => 'Telemetry is off.',
        'view_report' => 'View report',
        'report_title' => 'Telemetry report',
        'report_description' => 'Exactly what the next report sends.',
        'close' => 'Close',
    ],
];
