<?php
namespace Szurubooru\Helpers;

class MimeHelper
{
	public static function getMimeTypeFromFile($path)
	{
		$finfo = new \finfo(FILEINFO_MIME);
		return self::stripCharset($finfo->load($path));
	}

	public static function getMimeTypeFromBuffer($buffer)
	{
		$finfo = new \finfo(FILEINFO_MIME);
		return self::stripCharset($finfo->buffer($buffer));
	}

	public static function isFlash($mime)
	{
		return $mime === 'application/x-shockwave-flash';
	}

	public static function isVideo($mime)
	{
		return $mime === 'application/ogg' or preg_match('/video\//', $mime);
	}

	public static function isImage($mime)
	{
		return in_array($mime, ['image/jpeg', 'image/png', 'image/gif']);
	}

	private static function stripCharset($mime)
	{
		return preg_replace('/;\s*charset.*$/', '', $mime);
	}
}
