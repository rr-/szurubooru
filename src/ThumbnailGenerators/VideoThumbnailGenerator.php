<?php
class VideoThumbnailGenerator implements IThumbnailGenerator
{
	public function generateFromFile($srcPath, $dstPath, $width, $height)
	{
		$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';

		$cmd = sprintf(
			'ffmpegthumbnailer -i"%s" -o"%s" -s0 -t"12%%"',
			$srcPath,
			$tmpPath);
		exec($cmd);

		if (file_exists($tmpPath))
		{
			$thumbnailGenerator = new ImageThumbnailGenerator();
			$ret = $thumbnailGenerator->generateFromFile($tmpPath, $dstPath, $width, $height);
			unlink($tmpPath);
			return $ret;
		}

		$cmd = sprintf(
			'ffmpeg -i "%s" -vframes 1 "%s"',
			$srcPath,
			$tmpPath);
		exec($cmd);

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
