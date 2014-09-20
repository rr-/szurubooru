<?php
namespace Szurubooru\Services\ThumbnailGenerators;

class FlashThumbnailGenerator implements IThumbnailGenerator
{
	const PROGRAM_NAME_DUMP_GNASH = 'dump-gnash';
	const PROGRAM_NAME_SWFRENDER = 'swfrender';

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

		if (\Szurubooru\Helpers\ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_DUMP_GNASH))
		{
			\Szurubooru\Helpers\ProgramExecutor::run(
				self::PROGRAM_NAME_DUMP_GNASH,
				[
					'--screenshot', 'last',
					'--screenshot-file', $tmpTargetPath,
					'-1',
					'-r1',
					'--max-advances', '15',
					$tmpSourcePath,
				]);
		}

		if (!file_exists($tmpTargetPath) and \Szurubooru\Helpers\ProgramExecutor::isProgramAvailable(self::PROGRAM_NAME_SWFRENDER))
		{
			\Szurubooru\Helpers\ProgramExecutor::run(
				self::PROGRAM_NAME_SWFRENDER,
				[
					'swfrender',
					$tmpSourcePath,
					'-o',
					$tmpTargetPath,
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
