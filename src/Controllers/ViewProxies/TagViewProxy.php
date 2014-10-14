<?php
namespace Szurubooru\Controllers\ViewProxies;

class TagViewProxy extends AbstractViewProxy
{
	public function fromEntity($tag, $config = [])
	{
		$result = new \StdClass;
		if ($tag)
		{
			$result->name = $tag->getName();
			$result->usages = $tag->getUsages();
			$result->banned = $tag->isBanned();
		}
		return $result;
	}
}
