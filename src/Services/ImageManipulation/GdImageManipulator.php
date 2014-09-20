<?php
namespace Szurubooru\Services\ImageManipulation;

class GdImageManipulator implements IImageManipulator
{
	public function loadFromBuffer($source)
	{
		try
		{
			return imagecreatefromstring($source);
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	public function getImageWidth($imageResource)
	{
		return imagesx($imageResource);
	}

	public function getImageHeight($imageResource)
	{
		return imagesy($imageResource);
	}

	public function resizeImage(&$imageResource, $width, $height)
	{
		$targetImageResource = imagecreatetruecolor($width, $height);

		imagecopyresampled(
			$targetImageResource,
			$imageResource,
			0, 0,
			0, 0,
			$width,
			$height,
			imagesx($imageResource),
			imagesy($imageResource));

		$imageResource = $targetImageResource;
	}

	public function cropImage(&$imageResource, $width, $height, $originX, $originY)
	{
		if ($originX + $width > imagesx($imageResource))
			$width = imagesx($imageResource) - $originX;
		if ($originY + $height > imagesy($imageResource))
			$height = imagesy($imageResource) - $originY;

		if ($originX < 0)
			$width = $width + $originX;
		if ($originY < 0)
			$height = $height + $originY;

		if ($width < 1)
			$width = 1;
		if ($height < 1)
			$height = 1;

		$imageResource = imagecrop($imageResource, [
			'x' => $originX,
			'y' => $originY,
			'width' => $width,
			'height' => $height]);
	}

	public function saveToBuffer($imageResource, $format)
	{
		ob_start();

		switch ($format)
		{
			case self::FORMAT_JPEG:
				imagejpeg($imageResource);
				break;

			case self::FORMAT_PNG:
				imagepng($imageResource);
				break;

			default:
		}

		$buffer = ob_get_contents();
		ob_end_clean();

		if (!$buffer)
			throw new \InvalidArgumentException('Not supported');
		return $buffer;
	}
}
