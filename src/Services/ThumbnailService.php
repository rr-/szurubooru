<?php
namespace Szurubooru\Services;
use Szurubooru\Config;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Services\ThumbnailGenerators\IThumbnailGenerator;
use Szurubooru\Services\ThumbnailGenerators\SmartThumbnailGenerator;

class ThumbnailService
{
	private $config;
	private $fileDao;
	private $thumbnailGenerator;

	public function __construct(
		Config $config,
		PublicFileDao $fileDao,
		SmartThumbnailGenerator $thumbnailGenerator)
	{
		$this->config = $config;
		$this->fileDao = $fileDao;
		$this->thumbnailGenerator = $thumbnailGenerator;
	}

	public function deleteUsedThumbnails($sourceName)
	{
		foreach ($this->getUsedThumbnailSizes() as $size)
		{
			list ($width, $height) = $size;
			$target = $this->getThumbnailName($sourceName, $width, $height);
			$this->fileDao->delete($target);
		}
	}

	public function generateIfNeeded($sourceName, $width, $height)
	{
		$thumbnailName = $this->getThumbnailName($sourceName, $width, $height);

		if (!$this->fileDao->exists($thumbnailName))
			$this->generate($sourceName, $width, $height);
	}

	public function generate($sourceName, $width, $height)
	{
		switch ($this->config->misc->thumbnailCropStyle)
		{
			case 'outside':
				$cropStyle = IThumbnailGenerator::CROP_OUTSIDE;
				break;

			case 'inside':
				$cropStyle = IThumbnailGenerator::CROP_INSIDE;
				break;

			default:
				throw new \InvalidArgumentException('Invalid thumbnail crop style');
		}

		$thumbnailName = $this->getThumbnailName($sourceName, $width, $height);

		$source = $this->fileDao->load($sourceName);
		$result = null;

		if (!$source)
			$source = $this->fileDao->load($this->getBlankThumbnailName());

		if ($source)
			$result = $this->thumbnailGenerator->generate($source, $width, $height, $cropStyle);

		if (!$result)
			$result = $this->fileDao->load($this->getBlankThumbnailName());

		$this->fileDao->save($thumbnailName, $result);
	}

	public function getUsedThumbnailSizes()
	{
		foreach (glob($this->fileDao->getFullPath('thumbnails') . DIRECTORY_SEPARATOR . '*x*') as $fn)
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
