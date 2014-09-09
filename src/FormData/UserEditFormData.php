<?php
namespace Szurubooru\FormData;

class UserEditFormData implements \Szurubooru\IValidatable
{
	public $userName;
	public $email;
	public $accessRank;
	public $password;
	public $avatarStyle;
	public $browsingSettings;

	public function __construct($inputReader = null)
	{
		if ($inputReader !== null)
		{
			$this->userName = $inputReader->userName;
			$this->email = $inputReader->email;
			$this->password = $inputReader->password;
			$this->accessRank = $inputReader->accessRank;
			$this->avatarStyle = $inputReader->avatarStyle;
			$this->avatarContent = $inputReader->avatarContent;
			$this->browsingSettings = $inputReader->browsingSettings;
		}
	}

	public function validate(\Szurubooru\Validator $validator)
	{
		if ($this->userName !== null)
			$this->validator->validateUserName($formData->userName);

		if ($formData->password !== null)
			$this->validator->validatePassword($formData->password);

		if ($formData->email !== null)
			$this->validator->validateEmail($formData->email);

		if ($formData->browsingSettings !== null)
		{
			if (!is_string($formData->browsingSettings))
				throw new \InvalidArgumentException('Browsing settings must be stringified JSON.');
			else if (strlen($formData->browsingSettings) > 2000)
				throw new \InvalidArgumentException('Stringified browsing settings can have at most 2000 characters.');
		}
	}
}
