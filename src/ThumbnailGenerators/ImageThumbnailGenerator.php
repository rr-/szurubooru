<?php
class ImageThumbnailGenerator implements IThumbnailGenerator
{
	public function generateFromFile($srcPath, $dstPath, $width, $height)
	{
		if (extension_loaded('imagick'))
			$realImageGenerator = new ImageImagickThumbnailGenerator();
		elseif (extension_loaded('gd'))
			$realImageGenerator = new ImageGdThumbnailGenerator();
		else
			return false;

		return $realImageGenerator->generateFromFile($srcPath, $dstPath, $width, $height);
	}
}
