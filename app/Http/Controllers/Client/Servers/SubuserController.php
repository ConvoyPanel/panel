<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\Server\ServerSubuserData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Client\Servers\Subusers\StoreSubuserRequest;
use App\Http\Requests\Client\Servers\Subusers\UpdateSubuserRequest;
use App\Models\Server;
use App\Models\ServerSubuser;
use App\Policies\ServerPolicy;
use App\Services\Servers\SubuserService;
use Spatie\LaravelData\DataCollection;

/**
 * Who else may reach this server, and what they may do on it.
 *
 * Owner-only throughout: the route group carries `can:manageSubusers,server`, and
 * {@see ServerPolicy::manageSubusers()} records why no sub-user permission opens
 * it. The write endpoints repeat the gate in their form requests, so neither half depends on the
 * other still being there.
 */
class SubuserController
{
    public function __construct(private SubuserService $subusers) {}

    public function index(Server $server)
    {
        $subusers = $server->subusers()->with('user.invite')->oldest('id')->get();

        return ServerSubuserData::collect($subusers, DataCollection::class);
    }

    public function store(StoreSubuserRequest $request, Server $server)
    {
        ['subuser' => $subuser, 'invite' => $invite] = $this->subusers->share(
            server: $server,
            actor: $request->user(),
            email: $request->string('email')->toString(),
            permissions: $request->array('permissions'),
        );

        // Subject is the server, not the invitee: the feed people read is the server's, and the
        // owner is the one who needs to see that a grant was handed out.
        Audit::record(
            AuditEvent::SERVER_SUBUSER_ADDED,
            subject: $server,
            properties: [
                'email' => $subuser->user->email,
                'permissions' => $subuser->permissions,
            ],
        );

        $data = ServerSubuserData::from($subuser);

        // The invite link comes back the once, the same way admin user creation returns it: mail
        // is not proof of delivery, and an owner who can copy the link is never blocked by an
        // install with no SMTP.
        return $invite === null ? $data : ['data' => $data, 'invite' => $invite];
    }

    public function update(UpdateSubuserRequest $request, Server $server, ServerSubuser $subuser)
    {
        $subuser->update(['permissions' => array_values(array_unique($request->array('permissions')))]);

        Audit::record(
            AuditEvent::SERVER_SUBUSER_UPDATED,
            subject: $server,
            properties: [
                'email' => $subuser->user->email,
                'permissions' => $subuser->permissions,
            ],
        );

        return ServerSubuserData::from($subuser);
    }

    public function destroy(Server $server, ServerSubuser $subuser)
    {
        // Read before the delete: afterwards the model's attributes are all that is left of it.
        $email = $subuser->user->email;

        $subuser->delete();

        Audit::record(
            AuditEvent::SERVER_SUBUSER_REMOVED,
            subject: $server,
            properties: ['email' => $email],
        );

        return response()->noContent();
    }
}
