<?php
class SmartThumbnailGenerator implements IThumbnailGenerator
{
	public function generateFromUrl($url, $dstPath, $width, $height)
	{
		$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';

		try
		{
			TransferHelper::download(
				$url,
				$tmpPath,
				null);
		}
		catch (SimpleException $e)
		{
			echo $e->getMessage();
			return false;
		}

		$ret = self::generateFromFile($tmpPath, $dstPath, $width, $height);

		unlink($tmpPath);
		return $ret;
	}

	public function generateFromFile($srcPath, $dstPath, $width, $height)
	{
		if (!file_exists($srcPath))
			return false;

		$mime = mime_content_type($srcPath);

		switch ($mime)
		{
			case 'application/x-shockwave-flash':
				$thumbnailGenerator = new FlashThumbnailGenerator();
				return $thumbnailGenerator->generateFromFile($srcPath, $dstPath, $width, $height);

			case 'video/mp4':
			case 'video/webm':
			case 'video/ogg':
			case 'application/ogg':
			case 'video/x-flv':
			case 'video/3gpp':
				$thumbnailGenerator = new VideoThumbnailGenerator();
				return $thumbnailGenerator->generateFromFile($srcPath, $dstPath, $width, $height);

			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
				$thumbnailGenerator = new ImageThumbnailGenerator();
				return $thumbnailGenerator->generateFromFile($srcPath, $dstPath, $width, $height);

			default:
				throw new SimpleException('Invalid thumbnail file type');
		}
	}
}
