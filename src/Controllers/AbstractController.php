<?php
namespace Szurubooru\Controllers;
use Szurubooru\Router;

abstract class AbstractController
{
	abstract function registerRoutes(Router $router);
}
