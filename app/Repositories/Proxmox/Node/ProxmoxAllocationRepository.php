<?php

namespace App\Repositories\Proxmox\Node;

use Exception;
use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Exceptions\Repository\Proxmox\NextVMIDRetrievalException;
use function str_contains;

class ProxmoxAllocationRepository extends ProxmoxRepository
{
    /**
     * @throws NextVMIDRetrievalException
     */
    public function getNextVMID(): int
    {
        try {
            $response = $this->getHttpClient()
                             ->get('/api2/json/cluster/nextid');

            return (int) $this->getData($response);
        } catch (Exception $e) {
            throw new NextVMIDRetrievalException();
        }
    }

    /**
     * @throws NextVMIDRetrievalException
     */
    public function isVMIDAvailable(int $vmid): bool
    {
        try {
            $this->getHttpClient()
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