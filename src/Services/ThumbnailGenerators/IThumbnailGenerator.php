<?php
namespace Szurubooru\Services\ThumbnailGenerators;

interface IThumbnailGenerator
{
	public function generateFromFile($srcPath, $dstPath, $width, $height);
}
