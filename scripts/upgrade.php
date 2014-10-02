<?php
require_once(__DIR__
	. DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . 'src'
	. DIRECTORY_SEPARATOR . 'Bootstrap.php');

$upgradeService = Szurubooru\Injector::get(\Szurubooru\Services\UpgradeService::class);
$upgradeService->runUpgradesVerbose();
