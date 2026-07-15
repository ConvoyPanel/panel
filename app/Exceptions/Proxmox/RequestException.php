<?php

namespace App\Exceptions\Proxmox;

use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Client\Response;

use function is_null;

class RequestException extends \Exception
{
    public function __construct(public Response $response)
    {
        parent::__construct($this->prepareMessage($response), $response->status());
    }

    protected function prepareMessage(Response $response): string
    {
        $summary = Message::bodySummary($response->toPsrResponse());
        $reason = $response->reason();

        return is_null($summary) ? $reason : $reason .= ":\n{$summary}\n";
    }
}
