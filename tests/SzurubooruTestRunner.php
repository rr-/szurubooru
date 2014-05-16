<?php
class SzurubooruTestRunner implements ITestRunner
{
	public function run()
	{
		$options = $this->getOptions();

		$this->resetEnvironment($options);
		if ($options->cleanDatabase)
			$this->cleanDatabase();
		$this->resetEnvironment($options);

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
				\Chibi\Database::rollback(function() use ($callback)
				{
					$callback();
				});
			});

		$testRunner->run();
	}

	private function getOptions()
	{
		$options = getopt('cf:', ['clean', 'filter:', 'driver:']);

		$ret = new SzurubooruTestOptions;

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
		$stmt = new \Chibi\Sql\RawStatement('DROP DATABASE IF EXISTS ' . $this->getMysqlDatabaseName());
		\Chibi\Database::exec($stmt);
		$stmt = new \Chibi\Sql\RawStatement('CREATE DATABASE ' . $this->getMysqlDatabaseName());
		\Chibi\Database::exec($stmt);
	}

	private function removeTestFolders()
	{
		$folders =
		[
			realpath(Core::getConfig()->main->filesPath),
			realpath(Core::getConfig()->main->thumbsPath),
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

	private function resetEnvironment($options)
	{
		$_SESSION = [];

		Core::prepareConfig(true);

		Core::getConfig()->main->dbDriver = $options->dbDriver;
		if ($options->dbDriver == 'sqlite')
		{
			Core::getConfig()->main->dbLocation = $this->getSqliteDatabasePath();
		}
		elseif ($options->dbDriver == 'mysql')
		{
			Core::getConfig()->main->dbLocation = $this->getMysqlDatabaseName();
			Core::getConfig()->main->dbUser = 'test';
			Core::getConfig()->main->dbPass = 'test';
		}

		$this->removeTestFolders();

		Core::prepareEnvironment(true);

		if ($options->dbDriver == 'mysql')
		{
			$stmt = new \Chibi\Sql\RawStatement('USE ' . $this->getMysqlDatabaseName());
			\Chibi\Database::execUnprepared($stmt);
		}
	}
}
