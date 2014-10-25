<?php
require_once(__DIR__
	. DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . 'src'
	. DIRECTORY_SEPARATOR . 'Bootstrap.php');

use Szurubooru\Injector;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Services\PostThumbnailService;

$size = isset($argv[1]) ? $argv[1] : 160;

$postDao = Injector::get(PostDao::class);
$postThumbnailService = Injector::get(PostThumbnailService::class);

foreach ($postDao->findAll() as $post)
{
	try
	{
		$thumbnailName = $postThumbnailService->generateIfNeeded($post, $size, $size);
		echo '.';
	}
	catch (Exception $e)
	{
		echo PHP_EOL . $post->getId() . ': ' . $e->getMessage() . PHP_EOL;
	}
}
echo PHP_EOL;
