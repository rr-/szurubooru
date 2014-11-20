<?php
namespace Szurubooru;

final class Route
{
	public $query;
	public $route;

	public function __construct($query, callable $route)
	{
		$this->query = $query;
		$this->route = $route;
		$this->regex = $this->getRegex();
	}

	public function handle($query, &$output)
	{
		$query = trim($query, '/');
		if (!preg_match($this->regex, $query, $matches))
			return false;
		$routeArguments = $this->getRouteArguments($matches);

		$func = $this->route;
		if (is_array($this->route) && $this->route[1] === 'work')
		{
			foreach ($matches as $key => $value)
				$this->route[0]->setArgument($key, $value);
			$output = $func();
		}
		else
		{
			$output = $func(...array_values($routeArguments));
		}

		return true;
	}

	private function getRegex()
	{
		$quotedQuery = preg_quote(trim($this->query, '/'), '/');
		return '/^' . preg_replace('/\\\?\:([a-zA-Z_-]*)/', '(?P<\1>[^\/]+)', $quotedQuery) . '$/i';
	}

	private function getRouteArguments($matches)
	{
		$reflectionFunction = is_array($this->route)
			? new \ReflectionMethod($this->route[0], $this->route[1])
			: new \ReflectionFunction($this->route);
		$arguments = [];
		foreach ($reflectionFunction->getParameters() as $reflectionParameter)
		{
			$key = $reflectionParameter->name;
			if (isset($matches[$key]))
				$arguments[$key] = $matches[$key];
			elseif ($reflectionParameter->isDefaultValueAvailable())
				$arguments[$key] = $reflectionParameter->getDefaultValue();
			else
				$arguments[$key] = null;
		}
		return $arguments;
	}
}
