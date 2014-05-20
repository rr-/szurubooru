<?php
require_once __DIR__ . '/../src/core.php';

Access::disablePrivilegeChecking();

array_shift($argv);

$query = array_shift($argv);
$posts = PostSearchService::getEntities($query, null, null);
$entityCount = PostSearchService::getEntityCount($query, null, null);
$i = 0;
foreach ($posts as $post)
{
	++ $i;
	printf('%s (%d/%d)' . PHP_EOL, TextHelper::reprPost($post), $i, $entityCount);
	if (!$post->tryGetWorkingThumbnailPath())
		$post->generateThumbnail();
}

echo 'Don\'t forget to check access rights.' . PHP_EOL;
