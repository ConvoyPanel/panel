<?php

namespace App\Http\Controllers\Admin\Servers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Servers\StoreServerRequest;
use App\Jobs\Servers\ProcessInstallation;
use App\Models\IPAddress;
use App\Models\Server;
use App\Models\Template;
use App\Services\Servers\InstallService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ServerController extends Controller
{
    public function __construct(private InstallService $installService)
    {
    }

    public function index()
    {
        return Inertia::render('admin/servers/Index', [
            'servers' => Server::with(['template', 'owner:id,name,email', 'node:id,name'])->get(),
        ]);
    }

    public function show(Server $server)
    {
        return Inertia::render('admin/servers/Show', [
            'server' => $server,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/servers/Create');
    }

    public function store(StoreServerRequest $request)
    {
        if ($request->type === 'existing') {
            $server = Server::create($request->validated());

            if ($request->is_template === true) {
                Template::create([
                    'server_id' => $server->id,
                    'visible' => $request->is_visible ? $request->is_visible : false
                ]);
            }

            return redirect()->route('admin.servers.show', [$server->id]);
        }

        if ($request->type === 'new') {
            $addresses = [
                'ip' => null,
                'ip6' => null
            ];

            // now time to fetch IPs
            if (isset($request->addresses)) {
                foreach ($request->addresses as $_address) {
                    $address = IPAddress::find($_address);

                    if (isset($addresses[$address->type])) {
                        throw throw ValidationException::withMessages([
                            'addresses' => 'You cannot set multiple IPv4 or IPv6 addresses'
                        ]);
                    }

                    $addresses[$address->type] = [
                        'cidr' => "{$address->address}/{$address->cidr}",
                        'gateway' => $address->gateway,
                    ];
                }
            }

            $vmid = $request->vmid ? $request->vmid : random_int(1000, 999999999);
            $server = Server::create(array_merge($request->validated(), ['vmid' => $vmid]));

            // if nothing errors, we'll iterate again and this time set those IPs as locked
            if (isset($request->addresses)) {
                foreach ($request->addresses as $_address) {
                    IPAddress::find($_address)->update(['server_id' => $server->id]);
                }
            }

            ProcessInstallation::dispatch($request->template_id, $server->id, $server->node->cluster, $vmid, $addresses);

            return redirect()->route('admin.servers.show', [$server->id]);
        }
    }

    public function destroy(Server $server, Request $request)
    {
        if ($request->purge === true) {
            $this->installService->setServer($server)->delete();
        }

        $server->delete();

        return redirect()->route('admin.servers.index');
    }

    public function search(Request $request)
    {
        return Server::search($request->search)->get()->load(['node:id,name']);
    }
}
