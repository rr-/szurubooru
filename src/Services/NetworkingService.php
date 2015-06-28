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

        $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            ? $_SERVER['HTTP_IF_MODIFIED_SINCE']
            : false;

        $eTagHeader = isset($_SERVER['HTTP_IF_NONE_MATCH'])
            ? trim($_SERVER['HTTP_IF_NONE_MATCH'], "\" \t\r\n")
            : null;

        $this->httpHelper->setHeader('ETag', '"' . $eTag . '"');
        $this->httpHelper->setHeader('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $lastModified));
        $this->httpHelper->setHeader('Pragma', 'public');
        $this->httpHelper->setHeader('Cache-Control', 'public, max-age=' . $secondsToLive);
        $this->httpHelper->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $secondsToLive));
        $this->httpHelper->setHeader('Content-Type', MimeHelper::getMimeTypeFromFile($fullPath));

        if ($customFileName)
            $this->httpHelper->setHeader('Content-Disposition', 'inline; filename="' . $customFileName . '"');

        if (strtotime($ifModifiedSince) === $lastModified || $eTagHeader === $eTag)
        {
            $this->httpHelper->setResponseCode(304);
        }
        else
        {
            $this->httpHelper->setResponseCode(200);
            readfile($fullPath);
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
        catch (Exception $e)
        {
            throw new \Exception('Cannot open URL for reading: ' . $e->getMessage());
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
