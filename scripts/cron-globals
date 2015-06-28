#!/usr/bin/php
<?php
require_once(__DIR__
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'src'
    . DIRECTORY_SEPARATOR . 'Bootstrap.php');

use \Szurubooru\Services\PostService;

$postService = Szurubooru\Injector::get(PostService::class);
$postService->updatePostGlobals();
