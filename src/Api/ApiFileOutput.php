<?php
/**
* Used for serializing files output from jobs
*/
class ApiFileOutput
{
	public $fileContent;
	public $fileName;

	public function __construct($filePath, $fileName)
	{
		$this->fileContent = file_get_contents($filePath);
		$this->fileName = $fileName;
		$this->lastModified = filemtime($filePath);
		$this->mimeType = mime_content_type($filePath);
	}
}
