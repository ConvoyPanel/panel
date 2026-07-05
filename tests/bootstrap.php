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
