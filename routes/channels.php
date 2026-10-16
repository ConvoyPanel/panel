<?php

use App\Enums\Admin\AdminPermission;
use App\Models\Server;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('server.{id}', function ($user, $id) {
    $server = Server::find($id);

    if ($server === null) {
        return false;
    }

    // The same three callers the client API admits: the owner, someone they shared it with, and
    // an operator who can read servers.
    return (int) $user->id === (int) $server->user_id
        || $server->subuserFor($user) !== null
        || $user->hasAdminPermission(AdminPermission::SERVERS_READ);
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('public', function () {
    return true;
});
