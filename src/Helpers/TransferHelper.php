<?php
class TransferHelper
{
	protected static $mocks = [];

	public static function download($srcUrl, $dstPath, $maxBytes = null)
	{
		if (isset(self::$mocks[$srcUrl]))
		{
			self::copy(self::$mocks[$srcUrl], $dstPath);
			chmod($dstPath, 0644);
			return;
		}

		set_time_limit(0);
		try
		{
			$srcHandle = fopen($srcUrl, 'rb');
		}
		catch (Exception $e)
		{
			throw new SimpleException('Cannot open URL for reading: ' . $e->getMessage());
		}
		if (!$srcHandle)
			throw new SimpleException('Cannot open URL for reading');

		$dstHandle = fopen($dstPath, 'w+b');
		if (!$dstHandle)
		{
			fclose($srcHandle);
			throw new SimpleException('Cannot open file for writing');
		}

		try
		{
			while (!feof($srcHandle))
			{
				$buffer = fread($srcHandle, 4 * 1024);
				if (fwrite($dstHandle, $buffer) === false)
					throw new SimpleException('Cannot write into file');
				fflush($dstHandle);
				if ($maxBytes !== null and ftell($dstHandle) > $maxBytes)
				{
					throw new SimpleException(
						'File is too big (maximum size: %s)',
						TextHelper::useBytesUnits($maxBytes));
				}
			}
		}
		finally
		{
			fclose($srcHandle);
			fclose($dstHandle);

			chmod($dstPath, 0644);
		}
	}

	public static function mockForDownload($url, $sourceFile)
	{
		self::$mocks[$url] = $sourceFile;
	}

	public static function copy($srcPath, $dstPath)
	{
		if ($srcPath == $dstPath)
			throw new SimpleException('Trying to copy file to the same location');

		copy($srcPath, $dstPath);
	}

	public static function remove($srcPath)
	{
		if (file_exists($srcPath))
			unlink($srcPath);
	}

	public static function createDirectory($dirPath)
	{
		if (file_exists($dirPath))
		{
			if (!is_dir($dirPath))
				throw new SimpleException($dirPath . ' exists, but it\'s not a directory');

			return;
		}

		mkdir($dirPath, 0777, true);
	}

	public static function handleUploadErrors($file)
	{
		switch ($file['error'])
		{
			case UPLOAD_ERR_OK:
				break;

			case UPLOAD_ERR_INI_SIZE:
				throw new SimpleException('File is too big (maximum size: %s)', ini_get('upload_max_filesize'));

			case UPLOAD_ERR_FORM_SIZE:
				throw new SimpleException('File is too big than it was allowed in HTML form');

			case UPLOAD_ERR_PARTIAL:
				throw new SimpleException('File transfer was interrupted');

			case UPLOAD_ERR_NO_FILE:
				throw new SimpleException('No file was uploaded');

			case UPLOAD_ERR_NO_TMP_DIR:
				throw new SimpleException('Server misconfiguration error: missing temporary folder');

			case UPLOAD_ERR_CANT_WRITE:
				throw new SimpleException('Server misconfiguration error: cannot write to disk');

			case UPLOAD_ERR_EXTENSION:
				throw new SimpleException('Server misconfiguration error: upload was canceled by an extension');

			default:
				throw new SimpleException('Generic file upload error (id: ' . $file['error'] . ')');
		}
		if (!is_uploaded_file($file['tmp_name']))
			throw new SimpleException('Generic file upload error');
	}
}
