<?php
/**
* Used for serializing files output from jobs
*/
class ApiFileOutput implements ISerializable
{
	public $fileContent;
	public $fileName;
	public $lastModified;
	public $mimeType;

	public function __construct($filePath, $fileName)
	{
		$this->fileContent = file_get_contents($filePath);
		$this->fileName = $fileName;
		$this->lastModified = filemtime($filePath);
		$this->mimeType = mime_content_type($filePath);
	}

	public function serializeToArray()
	{
		return
		[
			'name ' => $this->fileName,
			'modification-time' => $this->lastModified,
			'mime-type' => $this->mimeType,
			'content' => base64_encode(gzencode($this->fileContent)),
		];
	}
}
