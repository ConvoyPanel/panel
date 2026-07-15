<?php

namespace App\Services\Proxmox\Node;

use App\Exceptions\Proxmox\NextVMIDRetrievalException;
use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Exception;

use function str_contains;

class ProxmoxAllocationClient extends ProxmoxClient
{
    /**
     * @throws NextVMIDRetrievalException
     */
    public function getNextVMID(): int
    {
        try {
            $response = $this->getHttpClient()
                ->get('/api2/json/cluster/nextid')
                ->json();

            return (int) $this->getData($response);
        } catch (Exception $e) {
            throw new NextVMIDRetrievalException;
        }
    }

    /**
     * @throws NextVMIDRetrievalException
     */
    public function isVMIDAvailable(int $vmid): bool
    {
        try {
            $this->getHttpClient()
                ->bodyFormat('query')
                ->get('/api2/json/cluster/nextid', [
                    'vmid' => $vmid,
                ]);

            return true;
        } catch (RequestException $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                return false;
            }

            throw new NextVMIDRetrievalException(previous: $e);
        } catch (Exception $e) {
            throw new NextVMIDRetrievalException(previous: $e);
        }
    }
}
