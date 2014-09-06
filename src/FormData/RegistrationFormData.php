<?php
namespace Szurubooru\FormData;

class RegistrationFormData
{
	public $userName;
	public $password;
	public $email;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->userName = $inputReader->userName;
			$this->password = $inputReader->password;
			$this->email = $inputReader->email;
		}
	}
}
