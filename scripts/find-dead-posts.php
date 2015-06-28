<?php
require_once(__DIR__
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'src'
    . DIRECTORY_SEPARATOR . 'Bootstrap.php');

use Szurubooru\Injector;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Dao\PostDao;

$publicFileDao = Injector::get(PublicFileDao::class);
$postDao = Injector::get(PostDao::class);

$paths = [];
foreach ($postDao->findAll() as $post)
{
    $paths[] = $post->getContentPath();
    $paths[] = $post->getThumbnailSourceContentPath();
}

$paths = array_flip($paths);
foreach ($publicFileDao->listAll() as $path)
{
    if (dirname($path) !== 'posts')
        continue;
    if (!isset($paths[$path]))
    {
        echo $path . PHP_EOL;
        flush();
    }
}
