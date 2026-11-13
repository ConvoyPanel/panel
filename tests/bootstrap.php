<?php

/*
|--------------------------------------------------------------------------
| Test suite bootstrap
|--------------------------------------------------------------------------
|
| Points the test suite at a dedicated database when DB_TEST_DATABASE is set
| (ddev exports it; see .ddev/config.yaml), so RefreshDatabase's migrate:fresh
| can never drop the development database, and at in-memory cache, sessions,
| queue and mail.
|
| It has to happen here rather than in phpunit.xml's <env>: ddev exports
| DB_DATABASE into the container environment, PHP mirrors that into $_SERVER,
| and Laravel's Env repository reads $_SERVER before $_ENV and getenv(), so
| <env force="true"> can't win. Every layer is overwritten before the framework
| boots.
|
| CI sets no DB_TEST_DATABASE and runs against its own throwaway database.
|
*/

require __DIR__.'/../vendor/autoload.php';

$override = function (string $key, string $value): void {
    $_SERVER[$key] = $value;
    $_ENV[$key] = $value;
    putenv("{$key}={$value}");
};

if ($testDatabase = getenv('DB_TEST_DATABASE')) {
    $override('DB_DATABASE', $testDatabase);
}

// phpunit.xml's <env> values lose to ddev's container environment for the same
// reason, which pointed the suite at the dev project's Redis cache, sessions and
// queue and its mail server: a test that flushed the cache emptied the dev one.
// These are the same values phpunit.xml sets.
foreach ([
    'APP_ENV' => 'testing',
    'CACHE_DRIVER' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'array',
] as $key => $value) {
    $override($key, $value);
}
