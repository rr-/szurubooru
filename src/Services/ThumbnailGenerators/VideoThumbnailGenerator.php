<?php
namespace Szurubooru\Services\ThumbnailGenerators;

class VideoThumbnailGenerator implements IThumbnailGenerator
{
	private $imageThumbnailGenerator;

	public function __construct(ImageThumbnailGenerator $imageThumbnailGenerator)
	{
		$this->imageThumbnailGenerator = $imageThumbnailGenerator;
	}

	public function generate($srcPath, $dstPath, $width, $height)
	{
		if (!file_exists($srcPath))
			throw new \InvalidArgumentException($srcPath . ' does not exist');

		$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';

		$cmd = sprintf(
			'ffmpegthumbnailer -i"%s" -o"%s" -s0 -t"12%%"',
			$srcPath,
			$tmpPath);
		exec($cmd);

		if (file_exists($tmpPath))
		{
			$this->imageThumbnailGenerator->generate($tmpPath, $dstPath, $width, $height);
			unlink($tmpPath);
			return;
		}

		$cmd = sprintf(
			'ffmpeg -i "%s" -vframes 1 "%s"',
			$srcPath,
			$tmpPath);
		exec($cmd);

		if (file_exists($tmpPath))
		{
			$this->imageThumbnailGenerator->generate($tmpPath, $dstPath, $width, $height);
			unlink($tmpPath);
			return;
		}

		throw new \RuntimeException('Failed to generate thumbnail');
	}
}
