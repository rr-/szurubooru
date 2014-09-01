<?php
namespace Szurubooru\Services;

class PasswordService
{
	private $config;

	public function __construct(\Szurubooru\Config $config)
	{
		$this->config = $config;
	}

	//todo: refactor this to generic validation
	public function validatePassword($password)
	{
		if (!$password)
			throw new \DomainException('Password cannot be empty.');

		$minPasswordLength = intval($this->config->security->minPasswordLength);
		if (strlen($password) < $minPasswordLength)
			throw new \DomainException('Password must have at least ' . $minPasswordLength . ' character(s).');

		if (preg_match('/[^\x20-\x7f]/', $password))
		{
			throw new \DomainException(
				'Password should contain only characters from ASCII range to avoid potential problems with encoding.');
		}

		return true;
	}

	public function getHash($password)
	{
		return hash('sha256', $this->config->security->secret . '/' . $password);
	}
}
