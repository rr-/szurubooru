<?php
require_once __DIR__ . '/../src/core.php';
require_once __DIR__ . '/../src/upgrade.php';
\Chibi\Autoloader::registerFileSystem(__DIR__);

class TestRunner
{
	protected $dbPath;

	public function __construct()
	{
		$this->dbPath = __DIR__ . '/db.sqlite';
	}

	public function run($options)
	{
		$cleanDatabase = (isset($options['c']) or isset($options['clean']));

		if (isset($options['f']))
			$filter = $options['f'];
		elseif (isset($options['filter']))
			$filter = $options['filter'];
		else
			$filter = null;

		if ($cleanDatabase)
			$this->cleanDatabase();

		try
		{
			$this->resetEnvironment();
			upgradeDatabase();

			$testFixtures = $this->getTestFixtures($filter);
			$this->runAll($testFixtures);
		}
		finally
		{
			$this->removeTestFolders();
		}
	}

	protected function cleanDatabase()
	{
		if (file_exists($this->dbPath))
			unlink($this->dbPath);
	}

	protected function resetEnvironment()
	{
		$_SESSION = [];
		prepareConfig(true);
		getConfig()->main->dbDriver = 'sqlite';
		getConfig()->main->dbLocation = $this->dbPath;
		$this->removeTestFolders();
		prepareEnvironment(true);
	}

	protected function removeTestFolders()
	{
		$folders =
		[
			realpath(getConfig()->main->filesPath),
			realpath(getConfig()->main->thumbsPath),
			realpath(dirname(getConfig()->main->logsPath)),
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

	protected function getTestFixtures($filter)
	{
		$testFiles = [];
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__)) as $fileName)
		{
			$path = $fileName->getPathname();
			if (preg_match('/.*Test.php$/', $path))
				$testFiles []= $path;
		}

		$testClasses = \Chibi\Util\Reflection::loadClasses($testFiles);

		$classFilter = $filter;
		$methodFilter = null;
		if ($filter !== null and strpos($filter, '::') !== false)
		{
			list ($classFilter, $methodFilter) = explode('::', $filter);
		}

		if ($classFilter)
			$testClasses = array_filter($testClasses, function($className) use ($classFilter)
			{
				return stripos($className, $classFilter) !== false;
			});

		$testFixtures = [];

		foreach ($testClasses as $class)
		{
			$reflectionClass = new ReflectionClass($class);
			if ($reflectionClass->isAbstract())
				continue;

			$testFixture = new StdClass;
			$testFixture->class = $reflectionClass;
			$testFixture->methods = [];
			foreach ($reflectionClass->getMethods() as $method)
			{
				if ($methodFilter and stripos($method->name, $methodFilter) === false)
					continue;

				if (preg_match('/test/i', $method->name)
					and $method->isPublic()
					and $method->getNumberOfParameters() == 0)
				{
					$testFixture->methods []= $method;
				}
			}

			if (!empty($testFixture->methods))
				$testFixtures []= $testFixture;
		}

		return $testFixtures;
	}

	protected function runAll($testFixtures)
	{
		$startTime = microtime(true);

		$testNumber = 0;
		$resultPrinter = function($result) use (&$testNumber)
		{
			printf('%3d %-65s ', ++ $testNumber, $result->origin->class . '::' . $result->origin->name);

			if ($result->success)
				echo 'OK';
			else
				echo 'FAIL';

			printf(' [%.03fs]' . PHP_EOL, $result->duration);
			if ($result->exception)
			{
				echo '---' . PHP_EOL;
				echo $result->exception->getMessage() . PHP_EOL;
				echo $result->exception->getTraceAsString() . PHP_EOL;
				echo '---' . PHP_EOL . PHP_EOL;
			}
		};

		//run all the methods
		echo 'Starting tests' . PHP_EOL;

		$success = true;
		foreach ($testFixtures as $className => $testFixture)
		{
			$results = $this->runTestFixture($testFixture, $resultPrinter);

			foreach ($results as $result)
				$success &= $result->success;
		}

		printf('%3s %-65s %s [%.03fs]' . PHP_EOL,
			' ',
			'All tests',
			$success ? 'OK' : 'FAIL',
			microtime(true) - $startTime);

		return $success;
	}

	protected function runTestFixture($testFixture, $resultPrinter)
	{
		$instance = $testFixture->class->newInstance();

		$instance->setup();
		$results = [];
		foreach ($testFixture->methods as $method)
		{
			$result = $this->runTest(function() use ($method, $instance)
				{
					$method->invoke($instance);
				});
			$result->origin = $method;

			if ($resultPrinter !== null)
				$resultPrinter($result);

			$results []= $result;
		}
		$instance->teardown();
		return $results;
	}

	protected function runTest($callback)
	{
		$this->resetEnvironment();

		$startTime = microtime(true);
		$result = new TestResult();
		try
		{
			\Chibi\Database::rollback(function() use ($callback)
			{
				$callback();
			});
			$result->success = true;
		}
		catch (Exception $e)
		{
			$result->exception = $e;
			$result->success = false;
		}
		$endTime = microtime(true);
		$result->duration = $endTime - $startTime;
		return $result;
	}
}

class TestResult
{
	public $duration;
	public $success;
	public $exception;
	public $origin;
}

$options = getopt('cf:', ['clean', 'filter:']);
(new TestRunner)->run($options);
