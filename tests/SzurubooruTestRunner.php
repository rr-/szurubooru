<?php
require_once __DIR__ . '/../src/core.php';
\Chibi\Autoloader::registerFileSystem(__DIR__);

class SzurubooruTestRunner implements ITestRunner
{
	public function run()
	{
		$options = $this->getOptions();

		if ($options->help)
		{
			$this->printHelp();
			exit(0);
		}

		$this->prepareTestConfig($options);
		$this->connectToDatabase($options);
		if ($options->cleanDatabase)
			$this->cleanDatabase();
		$this->connectToDatabase($options);

		Core::upgradeDatabase();

		$testRunner = new ReflectionBasedTestRunner;
		$testRunner->setFilter($options->filter);

		$testRunner->setEnvironmentPrepareAction(function() use ($options)
			{
				$this->resetEnvironment($options);
			});

		$testRunner->setEnvironmentCleanAction(function()
			{
				$this->removeTestFolders();
			});

		$testRunner->setTestWrapperAction(function($callback)
			{
				Core::getDatabase()->rollback(function() use ($callback)
				{
					$callback();
				});
			});

		$success = $testRunner->run();
		$this->removeCache();
		return $success;
	}

	private function removeCache()
	{
		$cachePath = __DIR__
			. DIRECTORY_SEPARATOR . '..'
			. DIRECTORY_SEPARATOR . 'lib'
			. DIRECTORY_SEPARATOR . 'chibi-core'
			. DIRECTORY_SEPARATOR . 'cache';

		foreach (glob($cachePath . DIRECTORY_SEPARATOR . '*.dat') as $fn)
			unlink($fn);
	}

	private function printHelp()
	{
		readfile(__DIR__ . DIRECTORY_SEPARATOR . 'help.txt');
	}

	private function getOptions()
	{
		$options = getopt('cf:h', ['clean', 'filter:', 'driver:', 'help']);

		$ret = new SzurubooruTestOptions;

		$ret->help = (isset($options['h']) or isset($options['h']));

		$ret->cleanDatabase = (isset($options['c']) or isset($options['clean']));

		$ret->dbDriver = 'sqlite';
		if (isset($options['driver']))
			$ret->dbDriver = $options['driver'];

		$ret->filter = null;
		if (isset($options['f']))
			$ret->filter = $options['f'];
		if (isset($options['filter']))
			$ret->filter = $options['filter'];

		return $ret;
	}

	private function getSqliteDatabasePath()
	{
		return __DIR__ . '/db.sqlite';
	}

	private function getMysqlDatabaseName()
	{
		return 'booru_test';
	}

	private function cleanDatabase()
	{
		if (Core::getConfig()->main->dbDriver == 'sqlite')
		{
			$this->cleanSqliteDatabase();
		}
		elseif (Core::getConfig()->main->dbDriver == 'mysql')
		{
			$this->cleanMysqlDatabase();
		}
	}

	private function cleanSqliteDatabase()
	{
		$dbPath = $this->getSqliteDatabasePath();
		if (file_exists($dbPath))
			unlink($dbPath);
	}

	private function cleanMysqlDatabase()
	{
		$stmt = \Chibi\Sql\Statements::raw('DROP DATABASE IF EXISTS ' . $this->getMysqlDatabaseName());
		Core::getDatabase()->executeUnprepared($stmt);
		$stmt = \Chibi\Sql\Statements::raw('CREATE DATABASE ' . $this->getMysqlDatabaseName());
		Core::getDatabase()->executeUnprepared($stmt);
	}

	private function removeTestFolders()
	{
		$folders =
		[
			realpath(Core::getConfig()->main->filesPath),
			realpath(Core::getConfig()->main->thumbnailsPath),
			realpath(Core::getConfig()->main->avatarsPath),
			realpath(dirname(Core::getConfig()->main->logsPath)),
		];

		foreach ($folders as $folder)
			$this->removeTestFolder($folder);
	}

	private function removeTestFolder($folder)
	{
		if (!file_exists($folder))
			return;

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

	private function connectToDatabase($options)
	{
		Core::prepareDatabase();

		if ($options->dbDriver == 'mysql')
		{
			$stmt = \Chibi\Sql\Statements::raw('USE ' . $this->getMysqlDatabaseName());
			Core::getDatabase()->executeUnprepared($stmt);
		}
	}

	private function prepareTestConfig($options)
	{
		$config = new \Chibi\Config();
		$config->loadIni(Core::getConfig()->rootDir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'config.ini');
		$config->isForTests = true;

		$config->main->dbDriver = $options->dbDriver;
		if ($options->dbDriver == 'sqlite')
		{
			$config->main->dbLocation = $this->getSqliteDatabasePath();
		}
		elseif ($options->dbDriver == 'mysql')
		{
			$config->main->dbLocation = $this->getMysqlDatabaseName();
			$config->main->dbUser = 'test';
			$config->main->dbPass = 'test';
		}

		Core::setConfig($config);
	}

	private function resetEnvironment($options)
	{
		$_SESSION = [];
		Auth::setCurrentUser(null);

		$this->removeCache();
		$this->removeTestFolders();
		$this->prepareTestConfig($options);
		Core::prepareEnvironment();
	}
}
