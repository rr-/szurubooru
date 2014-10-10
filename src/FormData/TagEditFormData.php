<?php
namespace Szurubooru\FormData;
use Szurubooru\IValidatable;
use Szurubooru\Validator;

class TagEditFormData implements IValidatable
{
	public $name;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->name = $inputReader->name;
		}
	}

	public function validate(Validator $validator)
	{
		if ($this->name !== null)
			$validator->validatePostTags([$this->name]);
	}
}

