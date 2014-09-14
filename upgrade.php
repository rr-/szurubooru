<?php
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'AutoLoader.php');

$upgradeService = Szurubooru\Injector::get(\Szurubooru\Services\UpgradeService::class);
$upgradeService->runUpgradesVerbose();
