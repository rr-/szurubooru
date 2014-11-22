<?php
namespace Szurubooru\ViewProxies;

abstract class AbstractViewProxy
{
	public abstract function fromEntity($entity, $config = []);

	public function fromArray($entities, $config = [])
	{
		return array_values(array_map(
			function($entity) use ($config)
			{
				return static::fromEntity($entity, $config);
			},
			$entities));
	}
}
