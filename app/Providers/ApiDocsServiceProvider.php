<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * The API reference, generated from the routes, form requests and Data classes.
 *
 * Two documents, one per token API: `/api/docs` for the application API and `/api/docs/client`
 * for the client API. Under /api so the SPA's catch-all route leaves them alone. Who may read
 * them is `convoy.api.docs_public`: anyone, or any signed-in account.
 */
class ApiDocsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Scramble::ignoreDefaultRoutes();

        $bearer = fn (OpenApi $openApi) => $openApi->secure(SecurityScheme::http('bearer'));

        Scramble::configure()
            ->routes(fn (Route $route) => Str::startsWith($route->uri, 'api/application/'))
            ->withDocumentTransformers($bearer)
            ->expose(ui: 'api/docs', document: 'api/docs/application.json');

        Scramble::registerApi('client', [
            'api_path' => 'api/client',
            'export_path' => 'storage/app/api-docs/client.json',
            'info' => [
                'version' => config('scramble.info.version'),
                'description' => 'The client API: a user\'s own servers, for a personal access token. See docs/api.md for authentication and limits.',
            ],
            'ui' => ['title' => 'Convoy Client API'],
        ])
            ->routes(fn (Route $route) => Str::startsWith($route->uri, 'api/client/'))
            ->withDocumentTransformers($bearer)
            ->expose(ui: 'api/docs/client', document: 'api/docs/client.json');

        Gate::define('viewApiDocs', fn (?User $user = null) => config('convoy.api.docs_public') || $user !== null);
    }
}
