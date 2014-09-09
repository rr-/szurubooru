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

		$mime = mime_content_type($srcPath);

		if ($this->isFlash($mime))
			return $this->flashThumbnailGenerator->generate($srcPath, $dstPath, $width, $height);

		if ($this->isVideo($mime))
			return $this->videoThumbnailGenerator->generate($srcPath, $dstPath, $width, $height);

		if ($this->isImage($mime))
			return $this->imageThumbnailGenerator->generate($srcPath, $dstPath, $width, $height);

		throw new \InvalidArgumentException('Invalid thumbnail file type: ' . $mime);
	}

	private function isFlash($mime)
	{
		return $mime === 'application/x-shockwave-flash';
	}

	private function isVideo($mime)
	{
		return $mime === 'application/ogg' or preg_match('/video\//', $mime);
	}

	private function isImage($mime)
	{
		return in_array($mime, ['image/jpeg', 'image/png', 'image/gif']);
	}
}
