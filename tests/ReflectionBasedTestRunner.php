<?php
class ReflectionBasedTestRunner implements ITestRunner
{
	protected $filter = null;
	protected $testsPath = __DIR__;

	protected $environmentPrepareAction = null;
	protected $environmentCleanAction = null;
	protected $testWrapperAction = null;

	public function run()
	{
		$testFixtures = $this->getTestFixtures($this->filter);
		$success = $this->runAll($testFixtures);
		exit($success ? 0 : 1);
	}

	public function setFilter($filter)
	{
		$this->filter = $filter;
	}

	public function setTestsPath($testsPath)
	{
		$this->testsPath = $testsPath;
	}

	public function setEnvironmentPrepareAction($callback)
	{
		$this->environmentPrepareAction = $callback;
	}

	public function setEnvironmentCleanAction($callback)
	{
		$this->environmentCleanAction = $callback;
	}

	public function setTestWrapperAction($callback)
	{
		$this->testWrapperAction = $callback;
	}

	protected function getTestFixtures($filter)
	{
		$testFiles = [];
		$path = $this->testsPath;
		if (!$path)
			$path = __DIR__;
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $fileName)
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
		if ($this->environmentPrepareAction !== null)
			call_user_func($this->environmentPrepareAction);

		$startTime = microtime(true);
		$result = new TestResult();

		try
		{
			if ($this->testWrapperAction !== null)
				call_user_func($this->testWrapperAction, $callback);
			else
				$callback();
			$result->success = true;
		}
		catch (Exception $e)
		{
			$result->exception = $e;
			$result->success = false;
		}
		finally
		{
			if ($this->environmentCleanAction !== null)
				call_user_func($this->environmentCleanAction);
		}

		$endTime = microtime(true);
		$result->duration = $endTime - $startTime;
		return $result;
	}
}
