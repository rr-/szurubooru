<?php
require_once __DIR__ . '/../src/core.php';

function usage()
{
	echo 'Usage: ' . basename(__FILE__);
	echo ' QUERY' . PHP_EOL;
	return true;
}

array_shift($argv);
if (empty($argv))
	usage() and die;

$filesPath = rtrim(\Chibi\Registry::getConfig()->main->filesPath, DS);
$query = array_shift($argv);
$posts = Model_Post::getEntities($query, null, null);
foreach ($posts as $post)
{
	echo implode("\t",
	[
		$post->id,
		$post->name,
		$filesPath . DS . $post->name,
		$post->mimeType,
	]). PHP_EOL;
}
