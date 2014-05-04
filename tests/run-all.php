<?php
$configPath = __DIR__ . '/test.ini';

if (isset($_SERVER['argv']))
	$args = $_SERVER['argv'];
else
	$args = [];

try
{
	$dbPath = __DIR__ . '/db.sqlite';

	if (file_exists($dbPath) and in_array('-c', $args))
		unlink($dbPath);

	$configIni =
	[
		'[main]',
		'dbDriver = "sqlite"',
		'dbLocation = "' . $dbPath . '"',
		'filesPath  = "' . __DIR__ . 'files',
		'thumbsPath = "' . __DIR__ . 'thumbs',
		'logsPath = "/dev/null"',
		'[registration]',
		'needEmailForRegistering = 0',
		'needEmailForCommenting = 0',
		'needEmailForUploading = 0'
	];

	file_put_contents($configPath, implode(PHP_EOL, $configIni));

	require_once __DIR__ . '/../src/core.php';
	require_once __DIR__ . '/../src/upgrade.php';

	upgradeDatabase();

	runAll();
}
finally
{
	unlink($configPath);
}

function getTestMethods()
{
	//get all test methods
	$testClasses = \Chibi\Util\Reflection::loadClasses(glob(__DIR__ . '/*Test.php'));
	$testMethods = [];

	foreach ($testClasses as $class)
	{
		$reflectionClass = new ReflectionClass($class);
		foreach ($reflectionClass->getMethods() as $method)
		{
			if (preg_match('/test/i', $method->name))
			{
				$testMethods []= $method;
			}
		}
	}

	return $testMethods;
}

function runAll()
{
	$startTime = microtime(true);
	$testMethods = getTestMethods();

	echo 'Starting tests' . PHP_EOL;

	//get display names of the methods
	$labels = [];
	foreach ($testMethods as $key => $method)
		$labels[$key] = $method->class . '::' . $method->name;

	//ensure every label has the same length
	$maxLabelLength = max(array_map('strlen', $labels));
	foreach ($labels as &$label)
		$label = str_pad($label, $maxLabelLength + 1, ' ');

	$pad = count($testMethods) ? ceil(log10(count($testMethods))) : 0;

	//run all the methods
	$success = true;
	foreach ($testMethods as $key => $method)
	{
		$instance = new $method->class();
		$testStartTime = microtime(true);

		echo str_pad($key + 1, $pad, ' ', STR_PAD_LEFT) . ' ';
		echo $labels[$key] . '... ';

		unset($e);
		try
		{
			runSingle(function() use ($method, $instance)
				{
					$method->invoke($instance);
				});
			echo 'OK';
		}
		catch (Exception $e)
		{
			$success = false;
			echo 'FAIL';
		}

		printf(' [%.03fs]' . PHP_EOL, microtime(true) - $testStartTime);
		if (isset($e))
		{
			echo '---' . PHP_EOL;
			echo $e->getMessage() . PHP_EOL;
			echo $e->getTraceAsString() . PHP_EOL;
			echo '---' . PHP_EOL . PHP_EOL;
		}
	}

	printf('%s %s... %s [%.03fs]' . PHP_EOL,
		str_pad('', $pad, ' '),
		str_pad('All tests', $maxLabelLength + 1, ' '),
		$success ? 'OK' : 'FAIL',
		microtime(true) - $startTime);

	return $success;
}

function runSingle($callback)
{
	resetEnvironment();
	\Chibi\Database::rollback(function() use ($callback)
	{
		$callback();
	});
}
