<?php
namespace Szurubooru\FormData;
use Szurubooru\IValidatable;
use Szurubooru\Validator;

class PostNoteFormData implements IValidatable
{
	public $left;
	public $top;
	public $width;
	public $height;
	public $text;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->left = floatval($inputReader->left);
			$this->top = floatval($inputReader->top);
			$this->width = floatval($inputReader->width);
			$this->height = floatval($inputReader->height);
			$this->text = trim($inputReader->text);
		}
	}

	public function validate(Validator $validator)
	{
		$validator->validateMinLength($this->text, 3, 'Post note content');
	}
}
