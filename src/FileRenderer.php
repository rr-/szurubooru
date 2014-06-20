<?php
class FileRenderer
{
	public function render(FileRendererOptions $options)
	{
		$lastModified = $options->lastModified;
		$eTag = $options->fileHash;
		$ttl = $options->cacheDaysToLive * 24 * 3600;

		$ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
			? $_SERVER['HTTP_IF_MODIFIED_SINCE']
			: false;

		$eTagHeader = isset($_SERVER['HTTP_IF_NONE_MATCH'])
			? trim(trim($_SERVER['HTTP_IF_NONE_MATCH']), '"')
			: false;

		\Chibi\Util\Headers::set('ETag', '"' . $eTag . '"');
		\Chibi\Util\Headers::set('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $lastModified));
		\Chibi\Util\Headers::set('Pragma', 'public');
		\Chibi\Util\Headers::set('Cache-Control', 'public, max-age=' . $ttl);
		\Chibi\Util\Headers::set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $ttl));

		if (isset($options->customFileName))
		{
			\Chibi\Util\Headers::set(
				'Content-Disposition',
				'inline; filename="' . $options->customFileName . '"');
		}

		if (isset($options->mimeType))
		{
			\Chibi\Util\Headers::set('Content-Type', $options->mimeType);
		}

		if (strtotime($ifModifiedSince) == $lastModified or $eTagHeader == $eTag)
		{
			\Chibi\Util\Headers::setCode('304');
			exit;
		}

		echo $options->fileContent;

		flush();
	}
}
