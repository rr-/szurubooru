<?php
require_once __DIR__ . '/../src/core.php';

Access::disablePrivilegeChecking();

array_shift($argv);

$query = array_shift($argv);
$posts = PostSearchService::getEntities($query, null, null);
foreach ($posts as $post)
{
	echo implode("\t",
	[
		$post->getId(),
		$post->getName(),
		$post->tryGetWorkingFullPath(),
		$post->getMimeType(),
	]). PHP_EOL;
}
