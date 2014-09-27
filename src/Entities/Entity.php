<?php
namespace Szurubooru\Entities;

abstract class Entity
{
	protected $id = null;
	private $lazyLoaders = [];
	private $lazyContainers = [];
	private $meta;

	public function __construct($id = null)
	{
		$this->id = $id === null ? null : intval($id);
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getMeta($metaName, $default = null)
	{
		if (!isset($this->meta[$metaName]))
			return $default;
		return $this->meta[$metaName];
	}

	public function setMeta($metaName, $value)
	{
		$this->meta[$metaName] = $value;
	}

	public function resetMeta()
	{
		$this->meta = [];
	}

	public function resetLazyLoaders()
	{
		$this->lazyLoaders = [];
		$this->lazyContainers = [];
	}

	public function setLazyLoader($lazyContainerName, $getter)
	{
		$this->lazyLoaders[$lazyContainerName] = $getter;
	}

	protected function lazyLoad($lazyContainerName, $defaultValue)
	{
		if (!isset($this->lazyContainers[$lazyContainerName]))
		{
			if (!isset($this->lazyLoaders[$lazyContainerName]))
			{
				return $defaultValue;
			}
			$result = $this->lazyLoaders[$lazyContainerName]($this);
			$this->lazySave($lazyContainerName, $result);
		}
		else
		{
			$result = $this->lazyContainers[$lazyContainerName];
		}
		return $result;
	}

	protected function lazySave($lazyContainerName, $value)
	{
		$this->lazyContainers[$lazyContainerName] = $value;
	}
}
