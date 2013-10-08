<?php
class ThumbnailHelper
{
	public static function cropOutside($srcImage, $dstWidth, $dstHeight)
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

	public static function cropInside($srcImage, $dstWidth, $dstHeight)
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
