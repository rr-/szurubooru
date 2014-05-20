<?php
class FlashThumbnailGenerator implements IThumbnailGenerator
{
	public function generateFromFile($srcPath, $dstPath, $width, $height)
	{
		if (!file_exists($srcPath))
			return false;

		$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.png';

		$cmd = sprintf(
			'dump-gnash --screenshot last --screenshot-file "%s" -1 -r1 --max-advances 15 "%s"',
			$tmpPath,
			$srcPath);
		exec($cmd);

		if (file_exists($tmpPath))
		{
			$thumbnailGenerator = new ImageThumbnailGenerator();
			$ret = $thumbnailGenerator->generateFromFile($tmpPath, $dstPath, $width, $height);
			unlink($tmpPath);
			return $ret;
		}

		exec('swfrender ' . $srcPath . ' -o ' . $tmpPath);

		if (file_exists($tmpPath))
		{
			$thumbnailGenerator = new ImageThumbnailGenerator();
			$ret = $thumbnailGenerator->generateFromFile($tmpPath, $dstPath, $width, $height);
			unlink($tmpPath);
			return $ret;
		}

		return false;
	}
}
