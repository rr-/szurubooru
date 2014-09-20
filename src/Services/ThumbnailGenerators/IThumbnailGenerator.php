<?php
namespace Szurubooru\Services\ThumbnailGenerators;

interface IThumbnailGenerator
{
	const CROP_OUTSIDE = 0;
	const CROP_INSIDE = 1;

	public function generate($sourceString, $width, $height, $cropStyle);
}
