<?php
namespace Szurubooru;

class Validator
{
	private $config;

	public function __construct(\Szurubooru\Config $config)
	{
		$this->config = $config;
	}

	public function validate(\Szurubooru\IValidatable $validatable)
	{
		$validatable->validate($this);
	}

	public function validateNumber($subject)
	{
		if (!preg_match('/^-?[0-9]+$/', $subject))
			throw new \DomainException($subject . ' does not look like a number.');
	}

	public function validateNonEmpty($subject, $subjectName = 'Object')
	{
		if (!$subject)
			throw new \DomainException($subjectName . ' cannot be empty.');
	}

	public function validateLength($subject, $minLength, $maxLength, $subjectName = 'Object')
	{
		$this->validateMinLength($subject, $minLength, $subjectName);
		$this->validateMaxLength($subject, $maxLength, $subjectName);
	}

	public function validateMinLength($subject, $minLength, $subjectName = 'Object')
	{
		if (strlen($subject) < $minLength)
			throw new \DomainException($subjectName . ' must have at least ' . $minLength . ' character(s).');
	}

	public function validateMaxLength($subject, $maxLength, $subjectName = 'Object')
	{
		if (strlen($subject) > $maxLength)
			throw new \DomainException($subjectName . ' must have at most ' . $maxLength . ' character(s).');
	}

	public function validateUserName($userName)
	{
		$minUserNameLength = intval($this->config->users->minUserNameLength);
		$maxUserNameLength = intval($this->config->users->maxUserNameLength);
		$this->validateNonEmpty($userName, 'User name');
		$this->validateLength($userName, $minUserNameLength, $maxUserNameLength, 'User name');

		if (preg_match('/[^a-zA-Z0-9_-]/', $userName))
		{
			throw new \DomainException('User name may contain only characters, numbers, underscore (_) and dash (-).');
		}
	}

	public function validateEmail($email)
	{
		if (!$email)
			return;

		if (!preg_match('/^[^@]+@[^@]+\.\w+$/', $email))
			throw new \DomainException('Specified e-mail appears to be invalid.');
	}

	public function validatePassword($password)
	{
		$minPasswordLength = intval($this->config->security->minPasswordLength);
		$this->validateNonEmpty($password, 'Password');
		$this->validateMinLength($password, $minPasswordLength, 'Password');

		if (preg_match('/[^\x20-\x7f]/', $password))
		{
			throw new \DomainException(
				'Password may contain only characters from ASCII range to avoid potential problems with encoding.');
		}
	}

	public function validateToken($token)
	{
		$this->validateNonEmpty($token, 'Token');
	}
}
