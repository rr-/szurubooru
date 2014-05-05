<?php
abstract class AbstractEntity implements IValidatable
{
	protected $id;
	protected $__cache = [];

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function resetCache()
	{
		$this->__cache = [];
	}

	public function setCache($key, $value)
	{
		$this->__cache[$key] = $value;
	}

	public function getCache($key)
	{
		return isset($this->__cache[$key])
			? $this->__cache[$key]
			: null;
	}

	public function hasCache($key)
	{
		return isset($this->__cache[$key]);
	}
}
