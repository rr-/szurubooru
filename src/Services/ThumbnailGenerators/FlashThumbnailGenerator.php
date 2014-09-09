<?php
namespace Szurubooru\Services\ThumbnailGenerators;

class FlashThumbnailGenerator implements IThumbnailGenerator
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

		$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.png';

		$cmd = sprintf(
			'dump-gnash --screenshot last --screenshot-file "%s" -1 -r1 --max-advances 15 "%s"',
			$tmpPath,
			$srcPath);
		exec($cmd);

		if (file_exists($tmpPath))
		{
			$this->imageThumbnailGenerator->generate($tmpPath, $dstPath, $width, $height);
			unlink($tmpPath);
			return;
		}

		exec('swfrender ' . $srcPath . ' -o ' . $tmpPath);

		if (file_exists($tmpPath))
		{
			$this->imageThumbnailGenerator->generate($tmpPath, $dstPath, $width, $height);
			unlink($tmpPath);
			return;
		}

		throw new \RuntimeException('Failed to generate thumbnail');
	}
}
