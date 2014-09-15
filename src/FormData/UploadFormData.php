<?php
namespace Szurubooru\FormData;

class UploadFormData implements \Szurubooru\IValidatable
{
	public $contentFileName;
	public $content;
	public $url;
	public $anonymous;
	public $safety;
	public $source;
	public $tags;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->contentFileName = $inputReader->contentFileName;
			$this->content = $inputReader->decodeBase64($inputReader->content);
			$this->url = $inputReader->url;
			$this->anonymous = $inputReader->anonymous;
			$this->safety = \Szurubooru\Helpers\EnumHelper::postSafetyFromString($inputReader->safety);
			$this->source = $inputReader->source;
			$this->tags = preg_split('/[\s+]/', $inputReader->tags);
		}
	}

	public function validate(\Szurubooru\Validator $validator)
	{
		if ($this->content === null and $this->url === null)
			throw new \DomainException('Neither data or URL provided.');

		$validator->validatePostTags($this->tags);

		if ($this->source !== null)
			$validator->validateMaxLength($this->source, 200, 'Source');
	}
}

