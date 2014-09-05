<?php
namespace Szurubooru\FormData;

class RegistrationFormData
{
	public $name;
	public $password;
	public $email;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->name = $inputReader->userName;
			$this->password = $inputReader->password;
			$this->email = $inputReader->email;
		}
	}
}
