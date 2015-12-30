<?php
namespace Szurubooru\Services;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\Helpers\HttpHelper;

class NetworkingService
{
    private $httpHelper;

    public function __construct(HttpHelper $httpHelper)
    {
        $this->httpHelper = $httpHelper;
    }

    public function serveFile($fullPath, $customFileName = null)
    {
        if (!file_exists($fullPath))
            throw new \Exception('File "' . $fullPath . '" does not exist.');

        $daysToLive = 7;
        $secondsToLive = $daysToLive * 24 * 60 * 60;
        $lastModified = filemtime($fullPath);
        $eTag = md5($fullPath . $lastModified);

        $ifModifiedSince = $this->httpHelper->getRequestHeader('HTTP_IF_MODIFIED_SINCE');
        $eTagHeader = trim($this->httpHelper->getRequestHeader('HTTP_IF_NONE_MATCH'), "\" \t\r\n");

        $this->httpHelper->setHeader('ETag', '"' . $eTag . '"');
        $this->httpHelper->setHeader('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $lastModified));
        $this->httpHelper->setHeader('Pragma', 'public');
        $this->httpHelper->setHeader('Cache-Control', 'public, max-age=' . $secondsToLive);
        $this->httpHelper->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $secondsToLive));
        $this->httpHelper->setHeader('Content-Transfer-Encoding', 'binary');
        $this->httpHelper->setHeader('Content-Type', MimeHelper::getMimeTypeFromFile($fullPath));
        $this->httpHelper->setHeader('Accept-Ranges', 'bytes');

        if ($customFileName)
            $this->httpHelper->setHeader('Content-Disposition', 'inline; filename="' . $customFileName . '"');

        if (strtotime($ifModifiedSince) === $lastModified || $eTagHeader === $eTag)
        {
            $this->httpHelper->setResponseCode(304);
            exit;
        }

        $fileSize = filesize($fullPath);
        $range = $this->httpHelper->getRequestHeader('HTTP_RANGE');
        if (!$range)
        {
            $this->httpHelper->setHeader('Content-Length', $fileSize);
            $this->httpHelper->setResponseCode(200);
            readfile($fullPath);
        }

        list ($param, $range) = explode('=', $range);
        if (strtolower(trim($param)) !== 'bytes')
        {
            $this->httpHelper->setResponseCode(400);
            exit;
        }
        $range = explode(',', $range);
        $range = explode('-', $range[0]);
        if (count($range) != 2)
        {
            $this->httpHelper->setResponseCode(400);
            exit;
        }
        if ($range[0] === '')
        {
            $end = $fileSize - 1;
            $start = $end - intval($range[0]);
        }
        else if ($range[1] === '')
        {
            $start = intval($range[0]);
            $end = $fileSize - 1;
        }
        else
        {
            $start = intval($range[0]);
            $end = intval($range[1]);
            if ($end >= $fileSize || (!$start && (!$end || $end == ($fileSize - 1))))
            {
                $this->httpHelper->setHeader('Content-Length', $fileSize);
                $this->httpHelper->setResponseCode(200);
                readfile($fullPath);
            }
        }
        $length = $end - $start + 1;

        $this->httpHelper->setResponseCode(206);
        $this->httpHelper->setHeader('Content-Range', sprintf('bytes %d-%d/%d', $start, $end, $fileSize));
        $fp = fopen($file, 'rb');
        try
        {
            if ($start)
                fseek($fp, $start);
            while ($length)
            {
                $read = ($length > 8192) ? 8192 : $length;
                $length -= $read;
                $this->httpHelper->output(fread($fp,$read));
            }
        }
        finally
        {
            fclose($fp);
        }
        exit;
    }

    public function download($url, $maxBytes = null)
    {
        set_time_limit(60);
        try
        {
            $srcHandle = fopen($url, 'rb');
        }
        catch (\Exception $e)
        {
            throw new \Exception('Cannot open URL "' . $url . '" for reading: ' . $e->getMessage());
        }

        if (!$srcHandle)
            throw new \Exception('Cannot open URL for reading');

        $result = '';
        try
        {
            while (!feof($srcHandle))
            {
                $buffer = fread($srcHandle, 4 * 1024);
                if ($maxBytes !== null && strlen($result) > $maxBytes)
                {
                    throw new \Exception(
                        'File is too big (maximum size: %s)',
                        TextHelper::useBytesUnits($maxBytes));
                }
                $result .= $buffer;
            }
        }
        finally
        {
            fclose($srcHandle);
        }

        return $result;
    }

    public function redirect($destination)
    {
        $this->httpHelper->redirect($destination);
    }

    public function nonCachedRedirect($destination)
    {
        $this->httpHelper->nonCachedRedirect($destination);
    }
}
