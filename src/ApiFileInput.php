<?php
/**
* Used for serializing files passed in POST requests to job arguments
*/
class ApiFileInput
{
	public $filePath;
	public $fileName;

	public function __construct($filePath, $fileName)
	{
		//todo: move_uploaded_file here
		//concerns post thumbs and post content
		$this->filePath = $filePath;
		$this->fileName = $fileName;
	}
}
