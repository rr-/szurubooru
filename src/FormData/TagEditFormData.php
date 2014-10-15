<?php
namespace Szurubooru\FormData;
use Szurubooru\IValidatable;
use Szurubooru\Validator;

class TagEditFormData implements IValidatable
{
	public $name;
	public $banned;
	public $implications;
	public $suggestions;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->name = $inputReader->name;

			if ($inputReader->banned !== null)
				$this->banned = boolval($inputReader->banned);

			$this->implications = array_filter(array_unique(preg_split('/[\s+]/', $inputReader->implications)));
			$this->suggestions = array_filter(array_unique(preg_split('/[\s+]/', $inputReader->suggestions)));
		}
	}

	public function validate(Validator $validator)
	{
		if ($this->name !== null)
			$validator->validatePostTags([$this->name]);

		if (!empty($this->implications))
			$validator->validatePostTags($this->implications);

		if (!empty($this->suggestions))
			$validator->validatePostTags($this->suggestions);
	}
}

