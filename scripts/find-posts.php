<?php
require_once __DIR__ . '/../src/core.php';

Access::disablePrivilegeChecking();

array_shift($argv);
$query = join(' ', $argv);

$posts = PostSearchService::getEntities($query, null, null);
foreach ($posts as $post)
{
	$info =
	[
		$post->getId(),
		$post->getName(),
		$post->getType()->toDisplayString(),
	];

	$additionalInfo = [];
	if ($post->getType()->toInteger() != PostType::Youtube)
	{
		$additionalInfo =
		[
			file_exists($post->getContentPath())
				? $post->getContentPath()
				: 'DOES NOT EXIST',
			$post->getMimeType(),
		];
	}

	echo implode("\t", array_merge($info, $additionalInfo)) . PHP_EOL;
}
