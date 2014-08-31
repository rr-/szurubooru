<?php
namespace Szurubooru;

class Router
{
	private $routes;

	public function get($query, $route)
	{
		$this->route('GET', $query, $route);
	}

	public function put($query, $route)
	{
		$this->route('PUT', $query, $route);
	}

	public function delete($query, $route)
	{
		$this->route('DELETE', $query, $route);
	}

	public function post($query, $route)
	{
		$this->route('POST', $query, $route);
	}

	private function route($method, $query, $route)
	{
		$this->routes[$method] []= new Route($query, $route);
	}

	public function handle($method, $request)
	{
		if (!isset($this->routes[$method]))
			throw new \DomainException('Unhandled request method: ' . $method);

		foreach ($this->routes[$method] as $route)
		{
			if ($route->handle($request, $output))
			{
				return $output;
				}
		}

		throw new \DomainException('Unhandled request address: ' . $request);
	}
}
