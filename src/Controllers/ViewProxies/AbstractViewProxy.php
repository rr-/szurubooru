<?php
namespace Szurubooru\Controllers\ViewProxies;

abstract class AbstractViewProxy
{
	public abstract function fromEntity($entity);

	public function fromArray($entities)
	{
		return array_map(function($entity) { return static::fromEntity($entity); }, $entities);
	}
}
