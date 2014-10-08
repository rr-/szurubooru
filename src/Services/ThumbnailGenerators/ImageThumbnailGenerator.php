<?php
namespace Szurubooru\Services\ThumbnailGenerators;
use Szurubooru\Services\ImageManipulation\IImageManipulator;
use Szurubooru\Services\ImageManipulation\ImageManipulator;

class ImageThumbnailGenerator implements IThumbnailGenerator
{
	private $imageManipulator;

	public function __construct(ImageManipulator $imageManipulator)
	{
		$this->imageManipulator = $imageManipulator;
	}

	public function generate($source, $width, $height, $cropStyle)
	{
		try
		{
			$image = $this->imageManipulator->loadFromBuffer($source);
			$srcWidth = $this->imageManipulator->getImageWidth($image);
			$srcHeight = $this->imageManipulator->getImageHeight($image);

			switch ($cropStyle)
			{
				case IThumbnailGenerator::CROP_OUTSIDE:
					$this->cropOutside($image, $srcWidth, $srcHeight, $width, $height);
					break;

				case IThumbnailGenerator::CROP_INSIDE:
					$this->cropInside($image, $srcWidth, $srcHeight, $width, $height);
					break;

				default:
					throw new \InvalidArgumentException('Unknown thumbnail crop style');
			}

			return $this->imageManipulator->saveToBuffer(
				$image,
				IImageManipulator::FORMAT_JPEG);
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	private function cropOutside(&$image, $srcWidth, $srcHeight, $dstWidth, $dstHeight)
	{
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

		$this->imageManipulator->resizeImage($image, $cropWidth, $cropHeight);
		$this->imageManipulator->cropImage($image, $dstWidth, $dstHeight, $cropX, $cropY);
	}

	private function cropInside(&$image, $srcWidth, $srcHeight, $dstWidth, $dstHeight)
	{
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

		$this->imageManipulator->resizeImage($image, $cropWidth, $cropHeight);
	}
}
