<?php
namespace Szurubooru\Services\ThumbnailGenerators;

interface IThumbnailGenerator
{
	public function generate($srcPath, $dstPath, $width, $height);
}
