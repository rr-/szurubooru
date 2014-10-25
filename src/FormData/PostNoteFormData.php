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
			$this->left = intval($inputReader->left);
			$this->top = intval($inputReader->top);
			$this->width = intval($inputReader->width);
			$this->height = intval($inputReader->height);
			$this->text = trim($inputReader->text);
		}
	}

	public function validate(Validator $validator)
	{
		$validator->validateMinLength($this->text, 3, 'Post note content');
	}
}
