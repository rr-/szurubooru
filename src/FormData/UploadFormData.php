<?php
namespace Szurubooru\FormData;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\IValidatable;
use Szurubooru\Validator;

class UploadFormData implements IValidatable
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
			$this->safety = EnumHelper::postSafetyFromString($inputReader->safety);
			$this->source = $inputReader->source;
			$this->tags = preg_split('/[\s+]/', $inputReader->tags);
		}
	}

	public function validate(Validator $validator)
	{
		if ($this->content === null && $this->url === null)
			throw new \DomainException('Neither data or URL provided.');

		$validator->validatePostTags($this->tags);

		if ($this->source !== null)
			$validator->validatePostSource($this->source);
	}
}

