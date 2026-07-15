<?php

return [
    // Comma-separated proxy IPs/CIDRs. Keep unset when Convoy is directly exposed: forwarded
    // client-IP headers must only be trusted when the immediate proxy is explicitly trusted.
    'proxies' => env('TRUSTED_PROXIES'),
];
