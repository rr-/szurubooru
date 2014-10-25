<?php
namespace Szurubooru\Services;
use Szurubooru\Config;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Helpers\ProgramExecutor;
use Szurubooru\Services\ImageManipulation\IImageManipulator;
use Szurubooru\Services\ThumbnailGenerator;

class ThumbnailService
{
	const PROGRAM_NAME_JPEGOPTIM = 'jpegoptim';
	const PROGRAM_NAME_OPTIPNG = 'optipng';

	private $config;
	private $fileDao;
	private $thumbnailGenerator;

	public function __construct(
		Config $config,
		PublicFileDao $fileDao,
		ThumbnailGenerator $thumbnailGenerator)
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
				$cropStyle = ThumbnailGenerator::CROP_OUTSIDE;
				break;

			case 'inside':
				$cropStyle = humbnailGenerator::CROP_INSIDE;
				break;

			default:
				throw new \InvalidArgumentException('Invalid thumbnail crop style');
		}

		$source = $this->fileDao->load($sourceName);
		if ($source)
		{
			$format = $this->getFormat();
			$thumbnailContent = $this->thumbnailGenerator->generate($source, $width, $height, $cropStyle, $format);
			if ($thumbnailContent)
			{
				$targetName = $this->getTargetName($sourceName, $width, $height);
				$this->fileDao->save($targetName, $thumbnailContent);
				$this->optimize($targetName, $format);
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

	private function getFormat()
	{
		return IImageManipulator::FORMAT_JPEG;
	}

	private function optimize($sourceName, $format)
	{
		$sourcePath = $this->fileDao->getFullPath($sourceName);

		if ($format === IImageManipulator::FORMAT_JPEG and ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_JPEGOPTIM))
		{
			ProgramExecutor::run(
				self::PROGRAM_NAME_JPEGOPTIM,
				[
					'--quiet',
					'--strip-all',
					$sourcePath,
				]);
		}

		elseif ($format === IImageManipulator::FORMAT_PNG and ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_OPTIPNG))
		{
			ProgramExecutor::run(
				self::PROGRAM_NAME_OPTIPNG,
				[
					'-o2',
					$sourcePath,
				]);
		}
	}
}
