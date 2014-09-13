<?php
namespace Szurubooru\Controllers\ViewProxies;

class TokenViewProxy extends AbstractViewProxy
{
	public function fromEntity($token)
	{
		$result = new \StdClass;
		if ($token)
		{
			$result->name = $token->getName();
			$result->purpose = $token->getPurpose();
		}
		return $result;
	}
}
