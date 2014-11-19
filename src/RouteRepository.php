<?php
namespace Szurubooru;

class RouteRepository
{
	private $routes = [];

	public function __construct(array $routes)
	{
		$this->routes = $routes;
	}

	public function getRoutes()
	{
		return $this->routes;
	}

	public function injectRoutes(Router $router)
	{
		foreach ($this->routes as $route)
		{
			foreach ($route->getMethods() as $method)
			{
				$method = strtolower($method);
				$router->$method($route->getUrl(), [$route, 'work']);
			}
		}
	}
}
