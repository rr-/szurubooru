<?php
namespace Szurubooru\Services\ThumbnailGenerators;

class ImageImagickThumbnailGenerator implements IThumbnailGenerator
{
	private $config;

	public function __construct(\Szurubooru\Config $config)
	{
		$this->config = $config;
	}

	public function generateFromFile($srcPath, $dstPath, $width, $height)
	{
		if (!file_exists($srcPath))
			throw new \InvalidArgumentException($srcPath . ' does not exist');

		$image = new \Imagick($srcPath);
		$image = $image->coalesceImages();

		switch ($this->config->misc->thumbnailCropStyle)
		{
			case 'outside':
				$this->cropOutside($image, $width, $height);
				break;
			case 'inside':
				$this->cropInside($image, $width, $height);
				break;
			default:
				throw new \Exception('Unknown thumbnail crop style');
		}

		$image->writeImage($dstPath);
		$image->destroy();
	}

	private function cropOutside($srcImage, $dstWidth, $dstHeight)
	{
		$srcWidth = $srcImage->getImageWidth();
		$srcHeight = $srcImage->getImageHeight();

		if (($dstHeight / $dstWidth) > ($srcHeight / $srcWidth))
		{
			$h = $dstHeight;
			$w = $h * $srcWidth / $srcHeight;
		}
		else
		{
			$w = $dstWidth;
			$h = $w * $srcHeight / $srcWidth;
		}
		$x = ($srcWidth - $w) / 2;
		$y = ($srcHeight - $h) / 2;

		$srcImage->resizeImage($w, $h, \imagick::FILTER_LANCZOS, 0.9);
		$srcImage->cropImage($dstWidth, $dstHeight, ($w - $dstWidth) >> 1, ($h - $dstHeight) >> 1);
		$srcImage->setImagePage(0, 0, 0, 0);
	}

	private function cropInside($srcImage, $dstWidth, $dstHeight)
	{
		$srcWidth = $srcImage->getImageWidth();
		$srcHeight = $srcImage->getImageHeight();

		if (($dstHeight / $dstWidth) < ($srcHeight / $srcWidth))
		{
			$h = $dstHeight;
			$w = $h * $srcWidth / $srcHeight;
		}
		else
		{
			$w = $dstWidth;
			$h = $w * $srcHeight / $srcWidth;
		}

		$srcImage->resizeImage($w, $h, \imagick::FILTER_LANCZOS, 0.9);
	}
}
