<?php
namespace Szurubooru\Helpers;

class MimeHelper
{
	public static function getMimeTypeFromFile($path)
	{
		$finfo = new \finfo(FILEINFO_MIME);
		return self::stripCharset($finfo->file($path));
	}

	public static function getMimeTypeFromBuffer($buffer)
	{
		$finfo = new \finfo(FILEINFO_MIME);
		return self::stripCharset($finfo->buffer($buffer));
	}

	public static function isFlash($mime)
	{
		return strtolower($mime) === 'application/x-shockwave-flash';
	}

	public static function isVideo($mime)
	{
		return strtolower($mime) === 'application/ogg' or preg_match('/video\//i', $mime);
	}

	public static function isImage($mime)
	{
		return in_array(strtolower($mime), ['image/jpeg', 'image/png', 'image/gif']);
	}

	private static function stripCharset($mime)
	{
		return preg_replace('/;\s*charset.*$/', '', $mime);
	}
}
