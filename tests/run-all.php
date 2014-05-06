<?php
require_once __DIR__ . '/../src/core.php';
require_once __DIR__ . '/../src/upgrade.php';
\Chibi\Autoloader::registerFileSystem(__DIR__);

$configPath = __DIR__ . '/test.ini';

$options = getopt('cf:', ['clean', 'filter:']);

$cleanDatabase = (isset($options['c']) or isset($options['clean']));

if (isset($options['f']))
	$filter = $options['f'];
elseif (isset($options['filter']))
	$filter = $options['filter'];
else
	$filter = null;

try
{
	$dbPath = __DIR__ . '/db.sqlite';

	if (file_exists($dbPath) and $cleanDatabase)
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

	upgradeDatabase();

	runAll($filter);
}
finally
{
	unlink($configPath);
}

function getTestMethods($filter)
{
	$testFiles = [];
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__)) as $fileName)
	{
		$path = $fileName->getPathname();
		if (preg_match('/.*Test.php$/', $path))
			$testFiles []= $path;
	}

	$testClasses = \Chibi\Util\Reflection::loadClasses($testFiles);

	if ($filter !== null)
	{
		$testClasses = array_filter($testClasses, function($className) use ($filter)
		{
			return stripos($className, $filter) !== false;
		});
	}

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

function runAll($filter)
{
	$startTime = microtime(true);
	$testMethods = getTestMethods($filter);

	echo 'Starting tests' . PHP_EOL;

	//get display names of the methods
	$labels = [];
	foreach ($testMethods as $key => $method)
		$labels[$key] = $method->class . '::' . $method->name;

	//ensure every label has the same length
	$maxLabelLength = count($testMethods) > 0 ? max(array_map('strlen', $labels)) : 0;
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
