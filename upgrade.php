<?php
require_once 'src/core.php';
$config = \Chibi\Registry::getConfig();

$dbVersion = Model_Property::get(Model_Property::DbVersion);
printf('DB version = %d' . PHP_EOL, $dbVersion);

$upgradesPath = TextHelper::absolutePath(\Chibi\Registry::getContext()->rootDir . DS . 'src' . DS . 'Upgrades');
$upgrades = glob($upgradesPath . DS . '*.sql');
natcasesort($upgrades);

foreach ($upgrades as $upgradePath)
{
	preg_match('/(\d+)\.sql/', $upgradePath, $matches);
	$upgradeVersion = intval($matches[1]);

	if ($upgradeVersion > $dbVersion)
	{
		printf('Executing %s...' . PHP_EOL, $upgradePath);
		$upgradeSql = file_get_contents($upgradePath);
		$upgradeSql = preg_replace('/^[ \t]+(.*);/m', '\0--', $upgradeSql);
		$queries = preg_split('/;\s*[\r\n]+/s', $upgradeSql);
		$queries = array_map('trim', $queries);
		foreach ($queries as $query)
		{
			echo $query . PHP_EOL;
			R::exec($query);
			echo PHP_EOL;
		}
	}

	Model_Property::set(Model_Property::DbVersion, $upgradeVersion);
}
