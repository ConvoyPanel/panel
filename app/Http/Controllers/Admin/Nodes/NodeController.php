<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nodes\Settings\UpdateNodeRequest;
use App\Http\Requests\Admin\Nodes\StoreNodeRequest;
use App\Models\Node;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Proxmox\PVE;

class NodeController extends ApplicationApiController
{
    public function index()
    {
        return Inertia::render('admin/nodes/Index', ['nodes' => Node::all()]);
    }

    public function create()
    {
        return Inertia::render('admin/nodes/Create');
    }

    public function show(Node $node)
    {
        return Inertia::render('admin/nodes/Show', [
            'node' => $node
        ]);
    }

    public function store(StoreNodeRequest $request)
    {
        $message = 'Unable to connect to server.';

        try {
            $proxmox = new PVE($request->hostname, $request->username, $request->password, intval($request->port), $request->auth_type);
            $response = $proxmox->nodes()->node($request->cluster)->tasks()->get(['start' => 0, 'limit' => 30]);

            if ($response === null) {
                throw ValidationException::withMessages([
                    'cluster' => $message,
                    'hostname' => $message,
                    'username' => $message,
                    'password' => $message,
                    'port' => $message,
                    'auth_type' => $message
                ]);
            }

            Node::create($request->validated());

            return $this->returnInertiaResponse($request, 'node-updated');
        } catch (Exception $exception) {
            throw ValidationException::withMessages([
                'cluster' => $message,
                'hostname' => $message,
                'username' => $message,
                'password' => $message,
                'port' => $message,
                'auth_type' => $message
            ]);
        }
    }

    public function search(Request $request)
    {
        return Node::search($request->search)->get();
    }

    public function update(Node $node, UpdateNodeRequest $request)
    {
        if (isset($request->username) && isset($request->password))
        {
            $node->update($request->safe()->all());

            return redirect()->route('admin.nodes.show.settings', [$node->id]);
        }

        if (isset($request->username))
        {
            $node->update($request->safe()->except(['password']));

            return redirect()->route('admin.nodes.show.settings', [$node->id]);
        }

        if (isset($request->password))
        {
            $node->update($request->safe()->except(['username']));

            return redirect()->route('admin.nodes.show.settings', [$node->id]);
        }

        if (!isset($request->username) && !isset($request->password))
        {
            $node->update($request->safe()->except(['username', 'password']));

            return redirect()->route('admin.nodes.show.settings', [$node->id]);
        }

        // fallback
        $node->update($request->safe()->all());

        return redirect()->route('admin.nodes.show.settings', [$node->id]);
    }

    public function destroy(Node $node)
    {
        $node->delete();

        return redirect()->route('admin.nodes');
    }
}
