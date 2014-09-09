<?php
namespace Szurubooru\Services\ThumbnailGenerators;

class ImageGdThumbnailGenerator implements IThumbnailGenerator
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

		$mime = mime_content_type($srcPath);

		switch ($mime)
		{
			case 'image/jpeg':
				$srcImage = imagecreatefromjpeg($srcPath);
				break;

			case 'image/png':
				$srcImage = imagecreatefrompng($srcPath);
				break;

			case 'image/gif':
				$srcImage = imagecreatefromgif($srcPath);
				break;

			default:
				throw new \Exception('Invalid thumbnail image type');
		}

		switch ($this->config->misc->thumbnailCropStyle)
		{
			case 'outside':
				$dstImage = $this->cropOutside($srcImage, $width, $height);
				break;
			case 'inside':
				$dstImage = $this->cropInside($srcImage, $width, $height);
				break;
			default:
				throw new \Exception('Unknown thumbnail crop style');
		}

		imagejpeg($dstImage, $dstPath);
		imagedestroy($srcImage);
		imagedestroy($dstImage);
	}

	private function cropOutside($srcImage, $dstWidth, $dstHeight)
	{
		$srcWidth = imagesx($srcImage);
		$srcHeight = imagesy($srcImage);

		if (($dstHeight / $dstWidth) > ($srcHeight / $srcWidth))
		{
			$cropHeight = $srcHeight;
			$cropWidth = $srcHeight * $dstWidth / $dstHeight;
		}
		else
		{
			$cropWidth = $srcWidth;
			$cropHeight = $srcWidth * $dstHeight / $dstWidth;
		}
		$cropX = ($srcWidth - $cropWidth) / 2;
		$cropY = ($srcHeight - $cropHeight) / 2;

		$dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
		imagecopyresampled($dstImage, $srcImage, 0, 0, $cropX, $cropY, $dstWidth, $dstHeight, $cropWidth, $cropHeight);
		return $dstImage;
	}

	private function cropInside($srcImage, $dstWidth, $dstHeight)
	{
		$srcWidth = imagesx($srcImage);
		$srcHeight = imagesy($srcImage);

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

		$dstImage = imagecreatetruecolor($cropWidth, $cropHeight);
		imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $cropWidth, $cropHeight, $srcWidth, $srcHeight);
		return $dstImage;
	}
}
