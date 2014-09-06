<?php
namespace Szurubooru\Services\ThumbnailGenerators;

class ImageThumbnailGenerator implements IThumbnailGenerator
{
	private $imageImagickThumbnailGenerator;
	private $imageGdThumbnailGenerator;

	public function __construct(
		ImageImagickThumbnailGenerator $imageImagickThumbnailGenerator,
		ImageGdThumbnailGenerator $imageGdThumbnailGenerator)
	{
		$this->imageImagickThumbnailGenerator = $imageImagickThumbnailGenerator;
		$this->imageGdThumbnailGenerator = $imageGdThumbnailGenerator;
	}

	public function generateFromFile($srcPath, $dstPath, $width, $height)
	{
		if (extension_loaded('imagick'))
			$strategy = $this->imageImagickThumbnailGenerator;
		elseif (extension_loaded('gd'))
			$strategy = $this->imageGdThumbnailGenerator;
		else
			throw new \Exception('Both imagick and gd extensions are disabled');

		return $strategy->generateFromFile($srcPath, $dstPath, $width, $height);
	}
}
