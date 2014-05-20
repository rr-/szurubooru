<?php
require_once __DIR__ . '/../src/core.php';

Access::disablePrivilegeChecking();

$options = getopt('f', ['force']);
$force = (isset($options['f']) or isset($options['force']));

$args = array_search('--', $argv);
$args = array_splice($argv, $args ? ++$args : (count($argv) - count($options)));

$query = array_shift($args);
$posts = PostSearchService::getEntities($query, null, null);
$entityCount = PostSearchService::getEntityCount($query, null, null);
$i = 0;
foreach ($posts as $post)
{
	++ $i;
	printf('%s (%d/%d)' . PHP_EOL, TextHelper::reprPost($post), $i, $entityCount);
	if ($post->tryGetWorkingThumbnailPath() and $force)
		unlink($post->tryGetWorkingThumbnailPath());
	if (!$post->tryGetWorkingThumbnailPath())
		$post->generateThumbnail();
}

echo 'Don\'t forget to check access rights.' . PHP_EOL;
