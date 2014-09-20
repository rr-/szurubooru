<?php
namespace Szurubooru\Services;

class ThumbnailService
{
	private $config;
	private $fileService;
	private $thumbnailGenerator;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Services\FileService $fileService,
		\Szurubooru\Services\ThumbnailGenerators\SmartThumbnailGenerator $thumbnailGenerator)
	{
		$this->config = $config;
		$this->fileService = $fileService;
		$this->thumbnailGenerator = $thumbnailGenerator;
	}

	public function deleteUsedThumbnails($sourceName)
	{
		foreach ($this->getUsedThumbnailSizes() as $size)
		{
			list ($width, $height) = $size;
			$target = $this->getThumbnailName($sourceName, $width, $height);
			$this->fileService->delete($target);
		}
	}

	public function generateIfNeeded($sourceName, $width, $height)
	{
		$thumbnailName = $this->getThumbnailName($sourceName, $width, $height);

		if (!$this->fileService->exists($thumbnailName))
			$this->generate($sourceName, $width, $height);
	}

	public function generate($sourceName, $width, $height)
	{
		switch ($this->config->misc->thumbnailCropStyle)
		{
			case 'outside':
				$cropStyle = \Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator::CROP_OUTSIDE;
				break;

			case 'inside':
				$cropStyle = \Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator::CROP_INSIDE;
				break;

			default:
				throw new \InvalidArgumentException('Invalid thumbnail crop style');
		}

		$thumbnailName = $this->getThumbnailName($sourceName, $width, $height);

		$source = $this->fileService->load($sourceName);
		$result = null;

		if (!$source)
			$source = $this->fileService->load($this->getBlankThumbnailName());

		if ($source)
			$result = $this->thumbnailGenerator->generate($source, $width, $height, $cropStyle);

		if (!$result)
			$result = $this->fileService->load($this->getBlankThumbnailName());

		$this->fileService->save($thumbnailName, $result);
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

	public function getThumbnailName($source, $width, $height)
	{
		return 'thumbnails' . DIRECTORY_SEPARATOR . $width . 'x' . $height . DIRECTORY_SEPARATOR . $source;
	}

	public function getBlankThumbnailName()
	{
		return 'thumbnails' . DIRECTORY_SEPARATOR . 'blank.png';
	}
}
