<?php

namespace App\Services\Nodes\Health;

use App\Models\Node;

class ResultsService
{
    public function getDetails()
    {
        return Node::selectRaw('name, cluster, hostname, port, TIMESTAMPDIFF(minute, last_pinged, NOW()) as last_ping_in_minutes')->get();
    }
}