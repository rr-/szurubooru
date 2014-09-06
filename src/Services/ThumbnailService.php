<?php
namespace Szurubooru\Services;

class ThumbnailService
{
	private $fileService;
	private $thumbnailGenerator;

	public function __construct(
		FileService $fileService,
		ThumbnailGenerators\SmartThumbnailGenerator $thumbnailGenerator)
	{
		$this->fileService = $fileService;
		$this->thumbnailGenerator = $thumbnailGenerator;
	}

	public function generateFromFile($source, $width, $height)
	{
		$target = $source . '-thumb' . $width . 'x' . $height . '.jpg';

		if (!$this->fileService->exists($target))
		{
			$fullSource = $this->fileService->getFullPath($source);
			$fullTarget = $this->fileService->getFullPath($target);
			$this->thumbnailGenerator->generateFromFile($fullSource, $fullTarget, $width, $height);
		}

		return $target;
	}
}
