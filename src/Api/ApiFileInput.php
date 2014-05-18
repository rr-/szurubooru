<?php
/**
* Used for serializing files passed in POST requests to job arguments
*/
class ApiFileInput
{
	public $filePath;
	public $fileName;
	public $originalPath;

	public function __construct($filePath, $fileName)
	{
		$tmpPath = tempnam(sys_get_temp_dir(), 'upload') . '.dat';
		$this->originalPath = $tmpPath;

		//php "security" bullshit
		if (is_uploaded_file($filePath))
			move_uploaded_file($filePath, $tmpPath);
		else
			copy($filePath, $tmpPath);

		$this->filePath = $tmpPath;
		$this->fileName = $fileName;
	}

	public function __destruct()
	{
		TransferHelper::remove($this->originalPath);
	}
}
