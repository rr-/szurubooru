<?php
namespace Szurubooru\Services\ThumbnailGenerators;
use Szurubooru\Helpers\MimeHelper;

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
		$mime = MimeHelper::getMimeTypeFromBuffer($source);

		if (MimeHelper::isFlash($mime))
			return $this->flashThumbnailGenerator->generate($source, $width, $height, $cropStyle);

		if (MimeHelper::isVideo($mime))
			return $this->videoThumbnailGenerator->generate($source, $width, $height, $cropStyle);

		if (MimeHelper::isImage($mime))
			return $this->imageThumbnailGenerator->generate($source, $width, $height, $cropStyle);

		return null;
	}
}
