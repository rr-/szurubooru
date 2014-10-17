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
	$thumbnailName = $postThumbnailService->generateIfNeeded($post, $size, $size);
	echo '.';
}
echo PHP_EOL;
