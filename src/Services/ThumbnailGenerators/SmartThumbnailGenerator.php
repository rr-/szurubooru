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

	public function generate($srcPath, $dstPath, $width, $height)
	{
		if (!file_exists($srcPath))
			throw new \InvalidArgumentException($srcPath . ' does not exist');

		$mime = \Szurubooru\Helpers\MimeHelper::getMimeTypeFromFile($srcPath);

		if (\Szurubooru\Helpers\MimeHelper::isFlash($mime))
			return $this->flashThumbnailGenerator->generate($srcPath, $dstPath, $width, $height);

		if (\Szurubooru\Helpers\MimeHelper::isVideo($mime))
			return $this->videoThumbnailGenerator->generate($srcPath, $dstPath, $width, $height);

		if (\Szurubooru\Helpers\MimeHelper::isImage($mime))
			return $this->imageThumbnailGenerator->generate($srcPath, $dstPath, $width, $height);

		throw new \InvalidArgumentException('Invalid thumbnail file type: ' . $mime);
	}
}
