<?php

/*
|--------------------------------------------------------------------------
| Test suite bootstrap
|--------------------------------------------------------------------------
|
| Redirects the test suite onto a dedicated database when DB_TEST_DATABASE is
| set (ddev exports it; see .ddev/config.yaml). This keeps RefreshDatabase's
| migrate:fresh from ever dropping the development database.
|
| It has to be done here rather than via phpunit.xml's <env>, because ddev
| exports DB_DATABASE into the container environment which PHP mirrors into
| $_SERVER, and Laravel's Env repository reads $_SERVER *before* $_ENV/getenv.
| PHPUnit's <env force="true"> only rewrites $_ENV + putenv, so it can't win —
| we have to overwrite every layer, including $_SERVER, before the framework
| boots.
|
| CI (and any environment that doesn't set DB_TEST_DATABASE) is left untouched
| and runs against its own throwaway database.
|
*/

require __DIR__.'/../vendor/autoload.php';

if ($testDatabase = getenv('DB_TEST_DATABASE')) {
    $_SERVER['DB_DATABASE'] = $testDatabase;
    $_ENV['DB_DATABASE'] = $testDatabase;
    putenv('DB_DATABASE='.$testDatabase);
}

/*
 * The same precedence problem, for the drivers phpunit.xml already asks for.
 *
 * ddev exports CACHE_STORE, QUEUE_CONNECTION and SESSION_DRIVER as real container env vars, so
 * PHPUnit's <env> entries lose to them exactly the way DB_DATABASE does above — which meant the
 * suite shared one Redis with the development app. A cached value then outlived the test that
 * wrote it and reappeared in the next run: `admin:overview` has a fifteen-second TTL, and a test
 * asserting on it passed or failed depending on how recently the suite had last run.
 */
foreach ([
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
] as $key => $value) {
    $_SERVER[$key] = $value;
    $_ENV[$key] = $value;
    putenv("{$key}={$value}");
}
