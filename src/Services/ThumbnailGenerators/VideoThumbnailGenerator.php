<?php
namespace Szurubooru\Services\ThumbnailGenerators;
use \Szurubooru\Helpers\ProgramExecutor;

class VideoThumbnailGenerator implements IThumbnailGenerator
{
	const PROGRAM_NAME_FFMPEG = 'ffmpeg';
	const PROGRAM_NAME_FFMPEGTHUMBNAILER = 'ffmpegthumbnailer';

	private $imageThumbnailGenerator;

	public function __construct(ImageThumbnailGenerator $imageThumbnailGenerator)
	{
		$this->imageThumbnailGenerator = $imageThumbnailGenerator;
	}

	public function generate($source, $width, $height, $cropStyle)
	{
		$tmpSourcePath = tempnam(sys_get_temp_dir(), 'thumb') . '.dat';
		$tmpTargetPath = tempnam(sys_get_temp_dir(), 'thumb') . '.png';
		file_put_contents($tmpSourcePath, $source);

		if (ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_FFMPEGTHUMBNAILER))
		{
			ProgramExecutor::run(
				self::PROGRAM_NAME_FFMPEGTHUMBNAILER,
				[
					'-i' . $tmpSourcePath,
					'-o' . $tmpTargetPath,
					'-s0',
					'-t12%%'
				]);
		}

		if (!file_exists($tmpTargetPath) and ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_FFMPEG))
		{
			ProgramExecutor::run(self::PROGRAM_NAME_FFMEPG,
				[
					'-i', $tmpSourcePath,
					'-vframes', '1',
					$tmpTargetPath
				]);
		}

		if (!file_exists($tmpTargetPath))
		{
			unlink($tmpSourcePath);
			return null;
		}

		$ret = $this->imageThumbnailGenerator->generate(file_get_contents($tmpTargetPath), $width, $height, $cropStyle);
		unlink($tmpSourcePath);
		unlink($tmpTargetPath);
		return $ret;
	}
}
