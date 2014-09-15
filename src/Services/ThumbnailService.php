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

	public function getOrGenerate($source, $width, $height)
	{
		$target = $this->getPath($source, $width, $height);

		if (!$this->fileService->exists($target))
			$this->generate($source, $width, $height);

		return $target;
	}

	public function deleteUsedThumbnails($source)
	{
		foreach ($this->getUsedThumbnailSizes() as $size)
		{
			list ($width, $height) = $size;
			$target = $this->getPath($source, $width, $height);
			if ($this->fileService->exists($target))
				$this->fileService->delete($target);
		}
	}

	public function generate($source, $width, $height)
	{
		$target = $this->getPath($source, $width, $height);

		$fullSource = $this->fileService->getFullPath($source);
		$fullTarget = $this->fileService->getFullPath($target);
		$this->fileService->createFolders($target);
		$this->thumbnailGenerator->generate($fullSource, $fullTarget, $width, $height);

		return $target;
	}

	public function getUsedThumbnailSizes()
	{
		foreach (glob($this->fileService->getFullPath('thumbnails') . DIRECTORY_SEPARATOR . '*x*') as $fn)
		{
			if (!is_dir($fn))
				continue;

			preg_match('/(?P<width>\d+)x(?P<height>\d+)/', $fn, $matches);
			if ($matches)
			{
				$width = intval($matches['width']);
				$height = intval($matches['height']);
				yield [$width, $height];
			}
		}
	}

	private function getPath($source, $width, $height)
	{
		return 'thumbnails' . DIRECTORY_SEPARATOR . $width . 'x' . $height . DIRECTORY_SEPARATOR . $source;
	}
}
