<?php
require_once __DIR__ . '/../src/core.php';

function usage()
{
	echo 'Usage: ' . basename(__FILE__);
	echo ' -print|-purge|-move DIR' . PHP_EOL;
	return true;
}

array_shift($argv);
if (empty($argv))
	usage() and die;

$action = array_shift($argv);
switch ($action)
{
	case '-print':
		$func = function($name)
		{
			echo $name . PHP_EOL;
		};
		break;

	case '-move':
		if (empty($argv))
			usage() and die;
		$dir = array_shift($argv);
		if (!file_exists($dir))
			mkdir($dir, 0755, true);
		if (!is_dir($dir))
			die($dir . ' is not a dir' . PHP_EOL);
		$func = function($name) use ($dir)
		{
			echo $name . PHP_EOL;
			$srcPath = Model_Post::getFullPath($name);
			$dstPath = $dir . DS . $name;
			rename($srcPath, $dstPath);
		};
		break;

	case '-purge':
		$func = function($name)
		{
			echo $name . PHP_EOL;
			$srcPath = Model_Post::getFullPath($name);
			unlink($srcPath);
		};
		break;

	default:
		die('Unknown action' . PHP_EOL);
}

$names = [];
foreach (R::findAll('post') as $post)
{
	$names []= $post->name;
}
$names = array_flip($names);

$config = \Chibi\Registry::getConfig();
foreach (glob(TextHelper::absolutePath($config->main->filesPath) . DS . '*') as $name)
{
	$name = basename($name);
	if (!isset($names[$name]))
	{
		$func($name);
	}
}
