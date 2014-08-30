<?php
namespace Szurubooru\Controllers;

abstract class AbstractController
{
	public static function register(\Szurubooru\Router $router)
	{
		return new static($router);
	}
}
