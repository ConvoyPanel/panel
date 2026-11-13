<?php

/*
 * Prints the panel's answers to a fixed set of API reads, so rehearse.sh can
 * diff v4 on MySQL against 5.x on the Postgres copy of the same data. Any
 * difference is a behaviour change the move to Postgres introduced.
 *
 * Boots whichever checkout it is run from (the current directory), so the same
 * file serves the v4 checkout and 5.x. It gives one application token a known
 * hash to call the API with: run it against rehearsal databases only.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

require getcwd().'/vendor/autoload.php';

$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$http = $app->make(HttpKernel::class);

// Nothing here should reach a node; answer anything that tries the same way on both sides.
Http::fake(['*' => Http::response(['errors' => 'rehearsal'], 503)]);

$token = DB::table('personal_access_tokens')->where('type', 'application')->orderBy('id')->first();
DB::table('personal_access_tokens')->where('id', $token->id)->update(['token' => hash('sha256', 'snapshot')]);

$server = DB::table('servers')->whereNull('status')->orderBy('id')->first();
$node = DB::table('nodes')->where('id', $server->node_id)->first();
$pool = DB::table('address_pools')->orderBy('id')->first();
$coterm = DB::table('coterms')->orderBy('id')->first();
$group = DB::table('template_groups')->where('node_id', $node->id)->orderBy('id')->first();
$owner = $app['auth']->createUserProvider('users')->retrieveById($server->user_id);

$application = [
    '/api/application/locations?per_page=100',
    '/api/application/nodes?per_page=100',
    '/api/application/nodes?filter[*]=PVE-US',
    '/api/application/nodes?filter[*]=not-an-id',
    '/api/application/nodes?filter[location_id]=1',
    "/api/application/nodes/{$node->id}",
    "/api/application/nodes/{$node->id}/addresses?per_page=1000",
    "/api/application/nodes/{$node->id}/template-groups",
    "/api/application/nodes/{$node->id}/template-groups/{$group->uuid}/templates",
    '/api/application/servers?per_page=100',
    "/api/application/servers?filter[node_id]={$node->id}",
    "/api/application/servers/{$server->uuid}",
    '/api/application/address-pools?per_page=100',
    "/api/application/address-pools/{$pool->id}",
    "/api/application/address-pools/{$pool->id}/attached-nodes",
    "/api/application/address-pools/{$pool->id}/addresses?per_page=1000",
    "/api/application/address-pools/{$pool->id}/addresses?filter[*]=198.51.100.10",
    '/api/application/coterms?per_page=100',
    "/api/application/coterms/{$coterm->id}/nodes",
];

// The client API takes a browser session, so these are made as the server's owner.
$client = [
    '/api/client/servers?per_page=100',
    "/api/client/servers/{$server->uuid}/backups?per_page=50",
    "/api/client/servers/{$server->uuid}/backups?per_page=50&sort=completed_at",
    "/api/client/servers/{$server->uuid}/backups?per_page=50&sort=-completed_at",
];

$print = function (string $uri, $response) {
    $body = json_decode($response->getContent(), true);
    echo "### GET {$uri}\n{$response->getStatusCode()}\n";
    echo (is_array($body) ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $response->getContent())."\n";
};

foreach ($application as $uri) {
    $print($uri, $http->handle(Request::create($uri, 'GET', server: [
        'HTTP_AUTHORIZATION' => "Bearer {$token->id}|snapshot",
        'HTTP_ACCEPT' => 'application/json',
    ])));
    $app->forgetInstance('request');
    $app['auth']->forgetGuards();
}

foreach ($client as $uri) {
    // The guard is built from the current request, so bind this one first.
    $request = Request::create($uri, 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
    $app->instance('request', $request);
    $app['auth']->guard('web')->setUser($owner);
    $print($uri, $http->handle($request));
    $app->forgetInstance('request');
    $app['auth']->forgetGuards();
}
