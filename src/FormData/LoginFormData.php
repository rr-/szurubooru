<?php
namespace Szurubooru\FormData;
use Szurubooru\IValidatable;
use Szurubooru\Validator;

class LoginFormData implements IValidatable
{
	public $userNameOrEmail;
	public $password;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->userNameOrEmail = trim($inputReader->userNameOrEmail);
			$this->password = $inputReader->password;
		}
	}

	public function validate(Validator $validator)
	{
	}
}
