<?php
require_once 'src/core.php';
$config = \Chibi\Registry::getConfig();

function getDbVersion()
{
	$dbVersion = Model_Property::get(Model_Property::DbVersion);
	if (strpos($dbVersion, '.') !== false)
	{
		list ($dbVersionMajor, $dbVersionMinor) = explode('.', $dbVersion);
	}
	elseif ($dbVersion)
	{
		$dbVersionMajor = $dbVersion;
		$dbVersionMinor = null;
	}
	else
	{
		$dbVersionMajor = 0;
		$dbVersionMinor = 0;
	}
	return [$dbVersionMajor, $dbVersionMinor];
}

$upgradesPath = TextHelper::absolutePath(\Chibi\Registry::getContext()->rootDir . DS . 'src' . DS . 'Upgrades' . DS . $config->main->dbDriver);
$upgrades = glob($upgradesPath . DS . '*.sql');
natcasesort($upgrades);

foreach ($upgrades as $upgradePath)
{
	preg_match('/(\d+)\.sql/', $upgradePath, $matches);
	$upgradeVersionMajor = intval($matches[1]);

	list ($dbVersionMajor, $dbVersionMinor) = getDbVersion();

	if (($upgradeVersionMajor > $dbVersionMajor) or ($upgradeVersionMajor == $dbVersionMajor and $dbVersionMinor !== null))
	{
		printf('%s: executing' . PHP_EOL, $upgradePath);
		$upgradeSql = file_get_contents($upgradePath);
		$upgradeSql = preg_replace('/^[ \t]+(.*);/m', '\0--', $upgradeSql);
		$queries = preg_split('/;\s*[\r\n]+/s', $upgradeSql);
		$queries = array_map('trim', $queries);
		$queries = array_filter($queries);
		$upgradeVersionMinor = 0;
		foreach ($queries as $query)
		{
			$query = preg_replace('/\s*--(.*?)$/m', '', $query);
			++ $upgradeVersionMinor;
			if ($upgradeVersionMinor > $dbVersionMinor)
			{
				try
				{
					R::exec($query);
				}
				catch (Exception $e)
				{
					echo $e . PHP_EOL;
					echo $query . PHP_EOL;
					die;
				}
				Model_Property::set(Model_Property::DbVersion, $upgradeVersionMajor . '.' . $upgradeVersionMinor);
			}
		}
		Model_Property::set(Model_Property::DbVersion, $upgradeVersionMajor);
	}
	else
	{
		printf('%s: no need to execute' . PHP_EOL, $upgradePath);
	}
}

list ($dbVersionMajor, $dbVersionMinor) = getDbVersion();
printf('Database version: %d.%d' . PHP_EOL, $dbVersionMajor, $dbVersionMinor);
