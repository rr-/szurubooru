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

	public static function getExtension($mime)
	{
		$map =
		[
			'application/x-shockwave-flash' => 'SWF',
			'image/jpeg' => 'JPG',
			'image/png' => 'PNG',
			'image/gif' => 'GIF',
			'video/3gpp' => '3GP',
			'video/annodex' => 'AXV',
			'video/dl' => 'DL',
			'video/dv' => 'dif DV',
			'video/fli' => 'FLI',
			'video/gl' => 'GL',
			'video/mpeg' => 'mpeg mpg MPE',
			'video/MP2T' => 'TS',
			'video/mp4' => 'MP4',
			'video/quicktime' => 'qt MOV',
			'video/ogg' => 'OGV',
			'video/webm' => 'WEBM',
			'video/vnd.mpegurl' => 'MXU',
			'video/x-flv' => 'FLV',
			'video/x-mng' => 'MNG',
			'video/x-ms-asf' => 'asf ASX',
			'video/x-ms-wm' => 'WM',
			'video/x-ms-wmv' => 'WMV',
			'video/x-ms-wmx' => 'WMX',
			'video/x-ms-wvx' => 'WVX',
			'video/x-msvideo' => 'AVI',
			'video/x-matroska' => 'MKV',
			'text/plain' => 'TXT',
		];
		$key = strtolower(trim($mime));
		return isset($map[$key]) ? $map[$key] : null;
	}

	private static function stripCharset($mime)
	{
		return preg_replace('/;\s*charset.*$/', '', $mime);
	}
}
