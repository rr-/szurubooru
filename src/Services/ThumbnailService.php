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
			$target = $this->getTargetName($sourceName, $width, $height);
			$this->fileDao->delete($target);
		}
	}

	public function generateIfNeeded($sourceName, $width, $height)
	{
		$targetName = $this->getTargetName($sourceName, $width, $height);

		if (!$this->fileDao->exists($targetName))
			return $this->generate($sourceName, $width, $height);

		return $targetName;
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

		$source = $this->fileDao->load($sourceName);
		if ($source)
		{
			$thumbnailContent = $this->thumbnailGenerator->generate($source, $width, $height, $cropStyle);
			if ($thumbnailContent)
			{
				$targetName = $this->getTargetName($sourceName, $width, $height);
				$this->fileDao->save($targetName, $thumbnailContent);
				return $targetName;
			}
		}

		return $this->getBlankThumbnailName();
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

	public function getTargetName($source, $width, $height)
	{
		return 'thumbnails' . DIRECTORY_SEPARATOR . $width . 'x' . $height . DIRECTORY_SEPARATOR . $source;
	}

	public function getBlankThumbnailName()
	{
		return 'thumbnails' . DIRECTORY_SEPARATOR . 'blank.png';
	}
}
