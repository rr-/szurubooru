<?php
namespace Szurubooru;

final class ControllerRepository
{
	private $controllers = [];

	public function __construct(array $controllers)
	{
		$this->controllers = $controllers;
	}

	public function getControllers()
	{
		return $this->controllers;
	}
}
