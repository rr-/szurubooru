<?php
namespace Szurubooru\Services;

class PasswordService
{
	private $config;
	private $alphabet;
	private $pattern;

	public function __construct(\Szurubooru\Config $config)
	{
		$this->config = $config;
		$this->alphabet =
		[
			'c' => str_split('bcdfghjklmnpqrstvwxyz'),
			'v' => str_split('aeiou'),
			'n' => str_split('0123456789'),
		];
		$this->pattern = str_split('cvcvnncvcv');
	}

	public function getHash($password)
	{
		return hash('sha256', $this->config->security->secret . '/' . $password);
	}

	public function getRandomPassword()
	{
		$password = '';
		foreach ($this->pattern as $token)
		{
			$subAlphabet = $this->alphabet[$token];
			$character = $subAlphabet[mt_rand(0, count($subAlphabet) - 1)];
			$password .= $character;
		}
		return $password;
	}
}
