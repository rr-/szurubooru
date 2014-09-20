<?php
namespace Szurubooru\Services\ImageManipulation;

interface IImageManipulator
{
	const FORMAT_JPEG = 0;
	const FORMAT_PNG = 1;

	public function loadFromBuffer($source);

	public function getImageWidth($imageResource);

	public function getImageHeight($imageResource);

	public function resizeImage(&$imageResource, $width, $height);

	public function cropImage(&$imageResource, $width, $height, $originX, $originY);

	public function saveToBuffer($imageResource, $format);
}
