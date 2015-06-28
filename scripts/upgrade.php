#!/usr/bin/php
<?php
require_once(__DIR__
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'src'
    . DIRECTORY_SEPARATOR . 'Bootstrap.php');

$testMode = false;

if (isset($argv))
{
    foreach ($argv as $arg)
    {
        if ($arg === '--test')
            $testMode = true;
    }
}

if ($testMode)
{
    $config = \Szurubooru\Injector::get(\Szurubooru\Config::class);
    $config->database->dsn = $config->database->tests->dsn;
    $config->database->user = $config->database->tests->user;
    $config->database->password = $config->database->tests->password;
    \Szurubooru\Injector::set(\Szurubooru\Config::class, $config);

    $databaseConnection = \Szurubooru\Injector::get(\Szurubooru\DatabaseConnection::class);
    $pdo = $databaseConnection->getPDO();
    $pdo->exec('DROP DATABASE IF EXISTS szuru_test');
    $pdo->exec('CREATE DATABASE szuru_test');
    $pdo->exec('USE szuru_test');
}

$upgradeService = \Szurubooru\Injector::get(\Szurubooru\Services\UpgradeService::class);
$upgradeService->runUpgradesVerbose();
