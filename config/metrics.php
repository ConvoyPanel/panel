<?php

return [
    /*
     | VictoriaMetrics backs the admin dashboard's KPI deltas and sparklines. It is OPTIONAL: when
     | the URL is empty the recorder and trend queries no-op and the dashboard still works (it just
     | omits deltas/sparklines), so it is never a required dependency for an operator's install.
     */
    'victoriametrics' => [
        'url' => env('VICTORIAMETRICS_URL'),
    ],
];
