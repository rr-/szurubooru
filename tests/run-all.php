<?php
require_once __DIR__ . '/../src/core.php';
\Chibi\Autoloader::registerFileSystem(__DIR__);

$dbPath = __DIR__ . '/db.sqlite';

function cleanDatabase()
{
	global $dbPath;
	if (file_exists($dbPath))
		unlink($dbPath);
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

function resetEnvironment()
{
	global $dbPath;

	$_SESSION = [];
	Core::prepareConfig(true);
	Core::getConfig()->main->dbDriver = 'sqlite';
	Core::getConfig()->main->dbLocation = $dbPath;
	removeTestFolders();
	Core::prepareEnvironment(true);
}

$options = getopt('cf:', ['clean', 'filter:']);
$cleanDatabase = (isset($options['c']) or isset($options['clean']));

if (isset($options['f']))
	$filter = $options['f'];
elseif (isset($options['filter']))
	$filter = $options['filter'];
else
	$filter = null;

resetEnvironment();
if ($cleanDatabase)
	cleanDatabase();
resetEnvironment();

Core::upgradeDatabase();

$testRunner = new TestRunner;
$testRunner->setFilter($filter);
$testRunner->setEnvironmentPrepareAction(function() { resetEnvironment(); });
$testRunner->setEnvironmentCleanAction(function() { removeTestFolders(); });
$testRunner->setTestWrapperAction(function($callback)
	{
		\Chibi\Database::rollback(function() use ($callback)
		{
			$callback();
		});
	});
$testRunner->run($filter);
