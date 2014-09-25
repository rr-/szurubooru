<?php
require_once(__DIR__
	. DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . 'src'
	. DIRECTORY_SEPARATOR . 'AutoLoader.php');

$postService = Szurubooru\Injector::get(\Szurubooru\Services\PostService::class);
$postService->updatePostGlobals();
