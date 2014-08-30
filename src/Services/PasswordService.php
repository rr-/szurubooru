<?php
namespace Szurubooru\Services;

class PasswordService
{
	private $config;

	public function __construct(\Szurubooru\Config $config)
	{
		$this->config = $config;
	}

	public function getHash($password)
	{
		return hash('sha256', $this->config->security->secret . '/' . $password);
	}
}
