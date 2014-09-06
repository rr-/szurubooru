<?php
namespace Szurubooru\FormData;

class UserEditFormData
{
	public $userName;
	public $email;
	public $accessRank;
	public $password;
	public $avatarStyle;

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
		}
	}
}
