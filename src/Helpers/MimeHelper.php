<?php
namespace Szurubooru\Helpers;

class MimeHelper
{
    public static function getMimeTypeFromFile($path)
    {
        $fh = fopen($path, 'rb');
        if (!$fh)
            throw new \Exception('Cannot open ' . $path . ' for reading');
        $bytes = fread($fh, 16);
        fclose($fh);
        return self::getMimeTypeFrom16Bytes($bytes);
    }

    public static function getMimeTypeFromBuffer($buffer)
    {
        return self::getMimeTypeFrom16Bytes(substr($buffer, 0, 16));
    }

    public static function isBufferAnimatedGif($buffer)
    {
        return strtolower(self::getMimeTypeFromBuffer($buffer)) === 'image/gif'
        and preg_match_all('#\x21\xf9\x04.{4}\x00[\x2c\x21]#s', $buffer) > 1;
    }

    public static function isFlash($mime)
    {
        return strtolower($mime) === 'application/x-shockwave-flash';
    }

    public static function isVideo($mime)
    {
        return strtolower($mime) === 'application/ogg' || preg_match('/video\//i', $mime);
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
            'video/webm' => 'WEBM',
            'video/mp4' => 'MP4',
            'video/mpeg' => 'MPEG MPG MPE',
            'video/x-flv' => 'FLV',
            'video/x-matroska' => 'MKV',
            'video/3gpp' => '3GP',
            'video/quicktime' => 'QT MOV',
            'text/plain' => 'TXT',
        ];
        $key = strtolower(trim($mime));
        return isset($map[$key]) ? $map[$key] : null;
    }

    private static function getMimeTypeFrom16Bytes($bytes)
    {
        if ($bytes === false)
            return false;

        if (strncmp($bytes, 'CWS', 3) === 0 || strncmp($bytes, 'FWS', 3) === 0 || strncmp($bytes, 'ZWS', 3) === 0)
            return 'application/x-shockwave-flash';

        if (strncmp($bytes, "\xff\xd8\xff", 3) === 0)
            return 'image/jpeg';

        if (strncmp($bytes, "\x89PNG\x0d\x0a", 6) === 0)
            return 'image/png';

        if (strncmp($bytes, 'GIF87a', 6) === 0 || strncmp($bytes, 'GIF89a', 6) === 0)
            return 'image/gif';

        if (strncmp($bytes, "\x1a\x45\xdf\xa3", 4) === 0)
            return 'video/webm';

        if (strncmp(substr($bytes, 4), 'ftypisom', 8) === 0 || strncmp(substr($bytes, 4), 'ftypmp42', 8) === 0)
            return 'video/mp4';

        if (strncmp($bytes, "\x46\x4c\x56\x01", 4) === 0)
            return 'video/x-flv';

        if (strncmp($bytes, "\x1a\x45\xdf\xa3\x93\x42\x82\x88\x6d\x61\x74\x72\x6f\x73\x6b\x61", 16) === 0)
            return 'video/x-matroska';

        if (strncmp($bytes, "\x00\x00\x00\x14\x66\x74\x79\x70\x33\x67\x70", 12) === 0 or
            strncmp($bytes, "\x00\x00\x00\x20\x66\x74\x79\x70\x33\x67\x70", 12) === 0)
            return 'video/3gpp';

        if (strncmp(substr($bytes, 4), 'ftypqt  ', 8) === 0)
            return 'video/quicktime';

        return 'application/octet-stream';
    }
}
