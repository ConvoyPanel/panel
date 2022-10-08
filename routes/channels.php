<?php

use Convoy\Models\Server;
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

Broadcast::channel('server.{id}', function ($user, $uuid) {
    return (int) $user->id === (int) App::make('Convoy\Repositories\Eloquent\ServerRepository')->getByUuid($uuid)->user_id || (bool) $user->root_admin;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('public', function () {
    return true;
});