<?php
namespace Szurubooru\Services;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\Helpers\ProgramExecutor;
use Szurubooru\Services\ImageManipulation\ImageManipulator;

class ImageConverter
{
	const PROGRAM_NAME_DUMP_GNASH = 'dump-gnash';
	const PROGRAM_NAME_SWFRENDER = 'swfrender';
	const PROGRAM_NAME_FFMPEG = 'ffmpeg';
	const PROGRAM_NAME_FFMPEGTHUMBNAILER = 'ffmpegthumbnailer';

	private $imageManipulator;

	public function __construct(ImageManipulator $imageManipulator)
	{
		$this->imageManipulator = $imageManipulator;
	}

	public function createImageFromBuffer($source)
	{
		$tmpSourcePath = tempnam(sys_get_temp_dir(), 'thumb') . '.dat';
		$tmpTargetPath = tempnam(sys_get_temp_dir(), 'thumb') . '.png';
		try
		{
			file_put_contents($tmpSourcePath, $source);
			if (!$this->convert($tmpSourcePath, $tmpTargetPath))
				throw new \Exception('Error while converting supplied file to image');

			$tmpSource = file_get_contents($tmpTargetPath);
			return $this->imageManipulator->loadFromBuffer($tmpSource);
		}
		finally
		{
			$this->deleteIfExists($tmpSourcePath);
			$this->deleteIfExists($tmpTargetPath);
		}
	}

	public function convert($sourcePath, $targetPath)
	{
		$mime = MimeHelper::getMimeTypeFromFile($sourcePath);

		if (MimeHelper::isImage($mime))
		{
			copy($sourcePath, $targetPath);
			return true;
		}

		if (MimeHelper::isFlash($mime))
			return $this->convertFromFlash($sourcePath, $targetPath);

		if (MimeHelper::isVideo($mime))
			return $this->convertFromVideo($sourcePath, $targetPath);

		return false;
	}

	private function convertFromFlash($sourcePath, $targetPath)
	{
		if (ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_DUMP_GNASH))
		{
			ProgramExecutor::run(
				self::PROGRAM_NAME_DUMP_GNASH,
				[
					'--screenshot', 'last',
					'--screenshot-file', $targetPath,
					'-1',
					'-r1',
					'--max-advances', '15',
					$sourcePath,
				]);
		}

		if (!file_exists($targetPath) and ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_SWFRENDER))
		{
			ProgramExecutor::run(
				self::PROGRAM_NAME_SWFRENDER,
				[
					'swfrender',
					$sourcePath,
					'-o',
					$targetPath,
				]);
		}

		if (!file_exists($targetPath))
			return false;

		return true;
	}

	private function convertFromVideo($sourcePath, $targetPath)
	{
		if (ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_FFMPEGTHUMBNAILER))
		{
			ProgramExecutor::run(
				self::PROGRAM_NAME_FFMPEGTHUMBNAILER,
				[
					'-i' . $sourcePath,
					'-o' . $targetPath,
					'-s0',
					'-t12%%'
				]);
		}

		if (!file_exists($targetPath) and ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_FFMPEG))
		{
			ProgramExecutor::run(self::PROGRAM_NAME_FFMEPG,
				[
					'-i', $sourcePath,
					'-vframes', '1',
					$targetPath
				]);
		}

		if (!file_exists($targetPath))
			return false;

		return true;
	}

	private function deleteIfExists($path)
	{
		if (file_exists($path))
			unlink($path);
	}
}
