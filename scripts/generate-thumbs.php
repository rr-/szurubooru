<?php
require_once __DIR__ . '/../src/core.php';

Access::disablePrivilegeChecking();

$query = [];
$force = false;

array_shift($argv);
foreach ($argv as $arg)
{
	if ($arg == '-f' or $arg == '--force')
		$force = true;
	else
		$query []= $arg;
}
$query = join(' ', $query);

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
