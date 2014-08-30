<?php
namespace Szurubooru;

final class ControllerRepository
{
	private $controllers = [];

	public function __construct(
		\Szurubooru\Controllers\AuthController $auth)
	{
		$this->controllers = func_get_args();
	}

	public function getControllers()
	{
		return $this->controllers;
	}
}
