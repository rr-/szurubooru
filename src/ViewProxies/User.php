<?php
namespace Szurubooru\ViewProxies;

class User
{
	public $name;

	public function __construct($user)
	{
		if (!$user)
			return;

		$this->name = $user->name;
	}
}
