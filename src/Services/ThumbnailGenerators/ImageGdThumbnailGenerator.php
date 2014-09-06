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
			$h = $srcHeight;
			$w = $h * $dstWidth / $dstHeight;
		}
		else
		{
			$w = $srcWidth;
			$h = $w * $dstHeight / $dstWidth;
		}
		$x = ($srcWidth - $w) / 2;
		$y = ($srcHeight - $h) / 2;

		$dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
		imagecopyresampled($dstImage, $srcImage, 0, 0, $x, $y, $dstWidth, $dstHeight, $w, $h);
		return $dstImage;
	}

	private function cropInside($srcImage, $dstWidth, $dstHeight)
	{
		$srcWidth = imagesx($srcImage);
		$srcHeight = imagesy($srcImage);

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

		$dstImage = imagecreatetruecolor($w, $h);
		imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $w, $h, $srcWidth, $srcHeight);
		return $dstImage;
	}
}
