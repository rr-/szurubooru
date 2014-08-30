<?php
namespace Szurubooru\Controllers;

abstract class AbstractController
{
	abstract function registerRoutes(\Szurubooru\Router $router);
}
