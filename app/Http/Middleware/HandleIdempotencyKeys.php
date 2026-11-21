<?php

namespace App\Http\Middleware;

use App\Exceptions\Http\Api\IdempotencyKeyInProgressException;
use App\Exceptions\Http\Api\IdempotencyKeyReusedException;
use App\Exceptions\Http\Api\InvalidIdempotencyKeyException;
use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Makes a token write safe to retry.
 *
 * A client that sends `Idempotency-Key: <anything unique>` with a write and then loses the
 * response (a timeout, a dropped connection) can send the same request with the same key again.
 * The second request does not run: it gets the first one's response back, with
 * `Idempotent-Replayed: true`. That is what stops a billing system that retries a create from
 * building two servers.
 *
 * - Keys are per token, so two integrations cannot collide on the same key.
 * - Only successful (2xx) responses are remembered. A request that failed validation did
 *   nothing, so it can be corrected and sent again under the same key.
 * - A key sent again with a different method, path or body is refused with 422, since it
 *   almost certainly means two operations were given one key by mistake.
 * - A key whose first request is still running gets 409 and a Retry-After.
 *
 * The panel's own session requests are left alone: the SPA never sends the header, and a
 * session has no token to scope a key to.
 */
class HandleIdempotencyKeys
{
    public const HEADER = 'Idempotency-Key';

    public const REPLAYED_HEADER = 'Idempotent-Replayed';

    /** Response headers worth replaying. The rest are per-request (dates, cookies, limits). */
    private const KEPT_HEADERS = ['content-type', 'location'];

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->headers->get(self::HEADER);
        $token = $request->user()?->currentAccessToken();

        if ($key === null || $request->isMethodSafe() || ! $token instanceof PersonalAccessToken) {
            return $next($request);
        }

        if ($key === '' || strlen($key) > 255) {
            throw new InvalidIdempotencyKeyException;
        }

        $cache = Cache::store();
        $entry = 'idempotency:'.$token->getKey().':'.hash('sha256', $key);
        $fingerprint = hash('sha256', $request->method().' '.$request->path()."\n".$request->getContent());

        if ($replay = $this->replay($cache, $entry, $fingerprint)) {
            return $replay;
        }

        $lock = Cache::lock($entry.':lock', 120);

        if (! $lock->get()) {
            throw new IdempotencyKeyInProgressException;
        }

        try {
            // Checked again under the lock: the first request may have finished between the
            // read above and taking the lock.
            if ($replay = $this->replay($cache, $entry, $fingerprint)) {
                return $replay;
            }

            $response = $next($request);

            if ($this->rememberable($response)) {
                $cache->put($entry, [
                    'fingerprint' => $fingerprint,
                    'status' => $response->getStatusCode(),
                    'headers' => array_intersect_key(
                        $response->headers->all(),
                        array_flip(self::KEPT_HEADERS),
                    ),
                    'content' => $response->getContent(),
                ], now()->addHours(max(1, (int) config('convoy.api.idempotency_ttl_hours', 24))));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    private function replay(Repository $cache, string $entry, string $fingerprint): ?Response
    {
        $stored = $cache->get($entry);

        if (! is_array($stored)) {
            return null;
        }

        if ($stored['fingerprint'] !== $fingerprint) {
            throw new IdempotencyKeyReusedException;
        }

        $response = new Response($stored['content'], $stored['status'], $stored['headers']);
        $response->headers->set(self::REPLAYED_HEADER, 'true');

        return $response;
    }

    private function rememberable(Response $response): bool
    {
        return $response->isSuccessful()
            && ! $response instanceof StreamedResponse
            && ! $response instanceof BinaryFileResponse;
    }
}
