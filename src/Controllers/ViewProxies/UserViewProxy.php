<?php
namespace Szurubooru\Controllers\ViewProxies;

class UserViewProxy extends AbstractViewProxy
{
	public function fromEntity($user)
	{
		$result = new \StdClass;
		if ($user)
		{
			$result->id = $user->id;
			$result->name = $user->name;
		}
		return $result;
	}
}
