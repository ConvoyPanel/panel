<?php

namespace App\Services\Audit;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Providers\AuditServiceProvider;
use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * Writes audit entries. Reached through the {@see Audit} facade so call sites stay a
 * single readable line:
 *
 *     Audit::record(AuditEvent::SERVER_POWER_START, subject: $server, properties: ['signal' => 'start']);
 *
 * Scoped to the request (see {@see AuditServiceProvider}) because the batch counter
 * is per-request state.
 *
 * See docs/audit-log-plan.md for why this exists rather than spatie/laravel-activitylog.
 */
class AuditLogger
{
    /** Nesting depth of the current batch; the UUID is cleared when it returns to zero. */
    private int $batchDepth = 0;

    private ?string $batchUuid = null;

    public function __construct(
        private readonly AuthFactory $auth,
        private readonly Application $app,
    ) {}

    /**
     * Records an action. Call this *after* the action has succeeded, or inside its transaction
     * where one exists — an action that rolls back must not leave an audit row behind.
     *
     * @param  Model|null  $subject  what was acted on; null only for panel-wide events like settings
     * @param  array<string, mixed>  $properties  event-specific detail, rendered by the frontend
     * @param  Model|null  $actor  overrides the authenticated user, for jobs and console commands
     */
    public function record(
        AuditEvent $event,
        ?Model $subject = null,
        array $properties = [],
        ?Model $actor = null,
    ): ?AuditLog {
        try {
            return $this->write($event, $subject, $properties, $actor);
        } catch (Throwable $exception) {
            // An audit failure must never take down the action being audited. Outside production
            // it still throws, so a broken call site fails loudly in development and in tests.
            if (config('app.env') !== 'production') {
                throw $exception;
            }

            Log::error('Failed to record audit entry', [
                'event' => $event->value,
                'exception' => $exception,
            ]);

            return null;
        }
    }

    /**
     * Runs the callback with every entry recorded inside it sharing one batch UUID, so the UI can
     * collapse "deleted 12 rules" into a single line. Nests safely.
     */
    public function batch(Closure $callback): mixed
    {
        if ($this->batchDepth === 0) {
            $this->batchUuid = Uuid::uuid4()->toString();
        }

        $this->batchDepth++;

        try {
            return $callback();
        } finally {
            $this->batchDepth--;

            if ($this->batchDepth === 0) {
                $this->batchUuid = null;
            }
        }
    }

    private function write(
        AuditEvent $event,
        ?Model $subject,
        array $properties,
        ?Model $actor,
    ): AuditLog {
        $log = new AuditLog([
            'event' => $event,
            'batch' => $this->batchUuid,
            'properties' => $properties,
            'api_token_id' => $this->apiTokenId(),
            'ip' => $this->request()?->ip(),
            // Truncated rather than rejected: a hostile or merely eccentric UA string must not be
            // able to fail somebody's power action.
            'user_agent' => $this->userAgent(),
        ]);

        $actor ??= $this->auth->guard()->user();

        if ($actor instanceof Model) {
            $log->actor()->associate($actor);
            $log->actor_label = $this->labelFor($actor);
        }

        if ($subject !== null) {
            $log->subject()->associate($subject);
        }

        $log->save();

        return $log;
    }

    /**
     * A human-readable snapshot of who acted, stored alongside the morph because the morph goes
     * null when the actor is deleted. Prefers the name, falls back to the email, and finally to a
     * type/id pair so the row is never anonymous.
     */
    private function labelFor(Model $actor): ?string
    {
        $label = $actor->getAttribute('name') ?? $actor->getAttribute('email');

        if (is_string($label) && $label !== '') {
            return mb_substr($label, 0, 255);
        }

        return class_basename($actor).'#'.$actor->getKey();
    }

    /**
     * The token behind this request, when there is one. Note that the resolved user may be a
     * SystemActor rather than a User (panel-wide application tokens); both use HasApiTokens, so
     * `currentAccessToken()` is available either way.
     */
    private function apiTokenId(): ?int
    {
        $user = $this->auth->guard()->user();

        if ($user === null || ! method_exists($user, 'currentAccessToken')) {
            return null;
        }

        $token = $user->currentAccessToken();

        return $token?->getKey();
    }

    private function userAgent(): ?string
    {
        $agent = $this->request()?->userAgent();

        return $agent === null ? null : mb_substr($agent, 0, 500);
    }

    /**
     * The HTTP request behind this action, or null when there isn't one.
     *
     * Gated on whether a route has actually been matched. Outside a real request the container
     * still hands back a synthetic Request whose ip() is 127.0.0.1 and whose REMOTE_ADDR is set,
     * and an audit trail claiming a scheduled prune came from localhost is worse than one that
     * records no IP at all. runningInConsole() cannot make this distinction (it is also true under
     * the test runner) and neither can REMOTE_ADDR; a resolved route can.
     */
    private function request(): ?Request
    {
        $request = $this->app->make(Request::class);

        return $request->route() === null ? null : $request;
    }
}
