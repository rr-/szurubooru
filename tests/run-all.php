<?php
require_once __DIR__ . '/../src/core.php';
\Chibi\Autoloader::registerFileSystem(__DIR__);

function getSqliteDatabasePath()
{
	return __DIR__ . '/db.sqlite';
}

function getMysqlDatabaseName()
{
	return 'booru_test';
}

function cleanDatabase()
{
	if (Core::getConfig()->main->dbDriver == 'sqlite')
	{
		$dbPath = getSqliteDatabasePath();
		if (file_exists($dbPath))
			unlink($dbPath);
	}
	elseif (Core::getConfig()->main->dbDriver == 'mysql')
	{
		$stmt = new \Chibi\Sql\RawStatement('DROP DATABASE IF EXISTS ' . getMysqlDatabaseName());
		\Chibi\Database::exec($stmt);
		$stmt = new \Chibi\Sql\RawStatement('CREATE DATABASE ' . getMysqlDatabaseName());
		\Chibi\Database::exec($stmt);
	}
}

function removeTestFolders()
{
	$folders =
	[
		realpath(Core::getConfig()->main->filesPath),
		realpath(Core::getConfig()->main->thumbsPath),
		realpath(dirname(Core::getConfig()->main->logsPath)),
	];

	foreach ($folders as $folder)
	{
		if (!file_exists($folder))
			continue;

		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$folder,
				FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($it as $path)
		{
			$path->isFile()
				? unlink($path->getPathname())
				: rmdir($path->getPathname());
		}
		rmdir($folder);
	}
}

function resetEnvironment($dbDriver)
{
	$_SESSION = [];
	Core::prepareConfig(true);
	Core::getConfig()->main->dbDriver = $dbDriver;
	if ($dbDriver == 'sqlite')
	{
		Core::getConfig()->main->dbLocation = getSqliteDatabasePath();
	}
	elseif ($dbDriver == 'mysql')
	{
		Core::getConfig()->main->dbLocation = getMysqlDatabaseName();
		Core::getConfig()->main->dbUser = 'test';
		Core::getConfig()->main->dbPass = 'test';
	}
	removeTestFolders();
	Core::prepareEnvironment(true);

	if ($dbDriver == 'mysql')
	{
		$stmt = new \Chibi\Sql\RawStatement('USE ' . getMysqlDatabaseName());
		\Chibi\Database::execUnprepared($stmt);
	}
}

$options = getopt('cf:', ['clean', 'filter:', 'driver:']);
$cleanDatabase = (isset($options['c']) or isset($options['clean']));
$dbDriver = isset($options['driver']) ? $options['driver'] : 'sqlite';

if (isset($options['f']))
	$filter = $options['f'];
elseif (isset($options['filter']))
	$filter = $options['filter'];
else
	$filter = null;

resetEnvironment($dbDriver);
if ($cleanDatabase)
	cleanDatabase();
resetEnvironment($dbDriver);

Core::upgradeDatabase();

$testRunner = new TestRunner;
$testRunner->setFilter($filter);
$testRunner->setEnvironmentPrepareAction(function() use ($dbDriver) { resetEnvironment($dbDriver); });
$testRunner->setEnvironmentCleanAction(function() { removeTestFolders(); });
$testRunner->setTestWrapperAction(function($callback)
	{
		\Chibi\Database::rollback(function() use ($callback)
		{
			$callback();
		});
	});
$testRunner->run($filter);
