<?php
namespace Szurubooru\Controllers\ViewProxies;

class TagViewProxy extends AbstractViewProxy
{
	public function fromEntity($tag)
	{
		$result = new \StdClass;
		if ($tag)
		{
			$result->name = $tag->getName();
		}
		return $result;
	}
}
