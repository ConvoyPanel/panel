<?php

/*
|--------------------------------------------------------------------------
| Test suite bootstrap
|--------------------------------------------------------------------------
|
| Points the test suite at a dedicated database when DB_TEST_DATABASE is set
| (ddev exports it; see .ddev/config.yaml), so RefreshDatabase's migrate:fresh
| can never drop the development database.
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

if ($testDatabase = getenv('DB_TEST_DATABASE')) {
    $_SERVER['DB_DATABASE'] = $testDatabase;
    $_ENV['DB_DATABASE'] = $testDatabase;
    putenv('DB_DATABASE='.$testDatabase);
}
