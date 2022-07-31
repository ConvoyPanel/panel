<?php

namespace App\Http\Controllers\Admin\Servers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Servers\Settings\UpdateBasicInfoRequest;
use App\Models\Server;
use App\Models\Template;
use App\Services\Servers\InstallService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Server $server)
    {
        return Inertia::render('admin/servers/settings/Index', [
            'server' => $server->load('template'),
        ]);
    }

    public function updateBasicInfo(Server $server, UpdateBasicInfoRequest $request)
    {
        if ($request->is_template === true)
        {
            if (isset($server->template))
            {
                $server->template->update(['visible' => $request->is_visible ? true : false]);
            } else {
                Template::create([
                    'server_id' => $server->id,
                    'visible' => $request->is_visible ? true : false,
                ]);
            }
        } elseif ($request->is_template === false) { //strict type checking
            if (isset($server->template))
            {
                $server->template->delete();
            }
        }

        $server->update($request->validated());

        return redirect()->route('admin.servers.show.settings', [$server->id]);
    }
}
