<?php
namespace Szurubooru\Routes;

abstract class AbstractRoute
{
	protected $arguments = [];

	public abstract function getMethods();

	public abstract function getUrl();

	public abstract function work();

	public function setArgument($argName, $argValue)
	{
		$this->arguments[$argName] = $argValue;
	}

	protected function getArgument($argName)
	{
		return $this->arguments[$argName];
	}
}
