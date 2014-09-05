<?php
namespace Szurubooru\Controllers\ViewProxies;

class TokenViewProxy extends AbstractViewProxy
{
	public function fromEntity($token)
	{
		$result = new \StdClass;
		if ($token)
		{
			$result->name = $token->name;
			$result->purpose = $token->purpose;
		}
		return $result;
	}
}
