<?php
namespace Szurubooru\Services\ThumbnailGenerators;

class ImageImagickThumbnailGenerator implements IThumbnailGenerator
{
	private $config;

	public function __construct(\Szurubooru\Config $config)
	{
		$this->config = $config;
	}

	public function generate($srcPath, $dstPath, $width, $height)
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
			$cropHeight = $dstHeight;
			$cropWidth = $dstHeight * $srcWidth / $srcHeight;
		}
		else
		{
			$cropWidth = $dstWidth;
			$cropHeight = $dstWidth * $srcHeight / $srcWidth;
		}
		$cropX = ($cropWidth - $dstWidth) >> 1;
		$cropY = ($cropHeight - $dstHeight) >> 1;

		$srcImage->resizeImage($cropWidth, $cropHeight, \imagick::FILTER_LANCZOS, 0.9);
		$srcImage->cropImage($dstWidth, $dstHeight, $cropX, $cropY);
		$srcImage->setImagePage(0, 0, 0, 0);
	}

	private function cropInside($srcImage, $dstWidth, $dstHeight)
	{
		$srcWidth = $srcImage->getImageWidth();
		$srcHeight = $srcImage->getImageHeight();

		if (($dstHeight / $dstWidth) < ($srcHeight / $srcWidth))
		{
			$cropHeight = $dstHeight;
			$cropWidth = $dstHeight * $srcWidth / $srcHeight;
		}
		else
		{
			$cropWidth = $dstWidth;
			$cropHeight = $dstWidth * $srcHeight / $srcWidth;
		}

		$srcImage->resizeImage($cropWidth, $cropHeight, \imagick::FILTER_LANCZOS, 0.9);
	}
}
