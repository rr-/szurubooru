<?php
namespace Szurubooru\Services\ThumbnailGenerators;

class SmartThumbnailGenerator implements IThumbnailGenerator
{
	private $flashThumbnailGenerator;
	private $videoThumbnailGenerator;
	private $imageThumbnailGenerator;

	public function __construct(
		FlashThumbnailGenerator $flashThumbnailGenerator,
		VideoThumbnailGenerator $videoThumbnailGenerator,
		ImageThumbnailGenerator $imageThumbnailGenerator)
	{
		$this->flashThumbnailGenerator = $flashThumbnailGenerator;
		$this->videoThumbnailGenerator = $videoThumbnailGenerator;
		$this->imageThumbnailGenerator = $imageThumbnailGenerator;
	}

	public function generate($source, $width, $height, $cropStyle)
	{
		$mime = \Szurubooru\Helpers\MimeHelper::getMimeTypeFromBuffer($source);

		if (\Szurubooru\Helpers\MimeHelper::isFlash($mime))
			return $this->flashThumbnailGenerator->generate($source, $width, $height, $cropStyle);

		if (\Szurubooru\Helpers\MimeHelper::isVideo($mime))
			return $this->videoThumbnailGenerator->generate($source, $width, $height, $cropStyle);

		if (\Szurubooru\Helpers\MimeHelper::isImage($mime))
			return $this->imageThumbnailGenerator->generate($source, $width, $height, $cropStyle);

		return null;
	}
}
