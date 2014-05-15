<?php
class ThumbnailHelper
{
	public static function cropOutside($srcImage, $dstWidth, $dstHeight)
	{
		$srcWidth = imagesx($srcImage);
		$srcHeight = imagesy($srcImage);

		if (($dstHeight / $dstWidth) > ($srcHeight / $srcWidth))
		{
			$h = $srcHeight;
			$w = $h * $dstWidth / $dstHeight;
		}
		else
		{
			$w = $srcWidth;
			$h = $w * $dstHeight / $dstWidth;
		}
		$x = ($srcWidth - $w) / 2;
		$y = ($srcHeight - $h) / 2;

		$dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
		imagecopyresampled($dstImage, $srcImage, 0, 0, $x, $y, $dstWidth, $dstHeight, $w, $h);
		return $dstImage;
	}

	public static function cropInside($srcImage, $dstWidth, $dstHeight)
	{
		$srcWidth = imagesx($srcImage);
		$srcHeight = imagesy($srcImage);

		if (($dstHeight / $dstWidth) < ($srcHeight / $srcWidth))
		{
			$h = $dstHeight;
			$w = $h * $srcWidth / $srcHeight;
		}
		else
		{
			$w = $dstWidth;
			$h = $w * $srcHeight / $srcWidth;
		}

		$dstImage = imagecreatetruecolor($w, $h);
		imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $w, $h, $srcWidth, $srcHeight);
		return $dstImage;
	}

	public static function generateFromUrl($url, $dstPath, $width, $height)
	{
		$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';

		TransferHelper::download(
			$url,
			$tmpPath,
			null);

		$ret = self::generateFromPath($tmpPath, $dstPath, $width, $height);

		unlink($tmpPath);
		return $ret;
	}

	public static function generateFromPath($srcPath, $dstPath, $width, $height)
	{
		$mime = mime_content_type($srcPath);

		switch ($mime)
		{
			case 'application/x-shockwave-flash':
				$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.png';

				$cmd = sprintf(
					'dump-gnash --screenshot last --screenshot-file "%s" -1 -r1 --max-advances 15 "%s"',
					$tmpPath,
					$srcPath);
				exec($cmd);

				if (file_exists($tmpPath))
				{
					$ret = self::generateFromPath($tmpPath, $dstPath, $width, $height);
					unlink($tmpPath);
					return $ret;
				}

				exec('swfrender ' . $srcPath . ' -o ' . $tmpPath);

				if (file_exists($tmpPath))
				{
					$ret = self::generateFromPath($tmpPath, $dstPath, $width, $height);
					unlink($tmpPath);
					return $ret;
				}

				return false;

			case 'video/mp4':
			case 'video/webm':
			case 'video/ogg':
			case 'application/ogg':
			case 'video/x-flv':
			case 'video/3gpp':
				$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';

				$cmd = sprintf(
					'ffmpegthumbnailer -i"%s" -o"%s" -s0 -t"12%%"',
					$srcPath,
					$tmpPath);
				exec($cmd);

				if (file_exists($tmpPath))
				{
					$ret = self::generateFromPath($tmpPath, $dstPath, $width, $height);
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
					$ret = self::generateFromPath($tmpPath, $dstPath, $width, $height);
					unlink($tmpPath);
					return $ret;
				}

				return false;

			case 'image/jpeg':
				$srcImage = imagecreatefromjpeg($srcPath);
				break;

			case 'image/png':
				$srcImage = imagecreatefrompng($srcPath);
				break;

			case 'image/gif':
				$srcImage = imagecreatefromgif($srcPath);
				break;

			default:
				throw new SimpleException('Invalid thumbnail file type');
		}

		$config = Core::getConfig();
		switch ($config->browsing->thumbStyle)
		{
			case 'outside':
				$dstImage = ThumbnailHelper::cropOutside($srcImage, $width, $height);
				break;
			case 'inside':
				$dstImage = ThumbnailHelper::cropInside($srcImage, $width, $height);
				break;
			default:
				throw new SimpleException('Unknown thumbnail crop style');
		}

		imagejpeg($dstImage, $dstPath);
		imagedestroy($srcImage);
		imagedestroy($dstImage);
	}
}
