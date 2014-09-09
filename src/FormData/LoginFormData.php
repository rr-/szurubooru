<?php
namespace Szurubooru\FormData;

class LoginFormData implements \Szurubooru\IValidatable
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

	public function validate(\Szurubooru\Validator $validator)
	{
	}
}
