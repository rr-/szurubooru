<?php
namespace Szurubooru\ViewProxies;

class User
{
	public $id;
	public $name;

	public function __construct($user)
	{
		if (!$user)
			return;

		$this->id = $user->id;
		$this->name = $user->name;
	}
}
