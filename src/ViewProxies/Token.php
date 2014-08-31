<?php
namespace Szurubooru\ViewProxies;

class Token
{
	public $name;
	public $purpose;

	public function __construct($token)
	{
		if (!$token)
			return;

		$this->name = $token->name;
		$this->purpose = $token->purpose;
	}
}
