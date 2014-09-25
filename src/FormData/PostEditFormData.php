<?php
namespace Szurubooru\FormData;

class PostEditFormData implements \Szurubooru\IValidatable
{
	public $content;
	public $thumbnail;
	public $safety;
	public $source;
	public $tags;
	public $relations;

	public $seenEditTime;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->content = $inputReader->decodeBase64($inputReader->content);
			$this->thumbnail = $inputReader->decodebase64($inputReader->thumbnail);
			$this->safety = \Szurubooru\Helpers\EnumHelper::postSafetyFromString($inputReader->safety);
			$this->source = $inputReader->source;
			$this->tags = preg_split('/[\s+]/', $inputReader->tags);
			$this->relations = array_filter(preg_split('/[\s+]/', $inputReader->relations));
			$this->seenEditTime = $inputReader->seenEditTime;
		}
	}

	public function validate(\Szurubooru\Validator $validator)
	{
		$validator->validatePostTags($this->tags);

		if ($this->source !== null)
			$validator->validatePostSource($this->source);

		if ($this->relations)
		{
			foreach ($this->relations as $relatedPostId)
				$validator->validateNumber($relatedPostId);
		}
	}
}
