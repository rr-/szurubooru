<?php
namespace Szurubooru;

class Router
{
    private $routes;

    public function get($url, callable $function)
    {
        $this->inject('GET', $url, $function);
    }

    public function post($url, callable $function)
    {
        $this->inject('POST', $url, $function);
    }

    public function put($url, callable $function)
    {
        $this->inject('PUT', $url, $function);
    }

    public function delete($url, callable $function)
    {
        $this->inject('DELETE', $url, $function);
    }

    public function handle($method, $request)
    {
        if (!isset($this->routes[$method]))
            throw new \DomainException('Unhandled request method: ' . $method);

        $request = trim($request, '/');
        foreach ($this->routes[$method] as $url => $callback)
        {
            if (!preg_match(self::getRegex($url), $request, $matches))
                continue;

            return $callback($matches);
        }

        throw new \DomainException('Unhandled request address: ' . $request);
    }

    private function inject($method, $url, callable $function)
    {
        if (!isset($this->routes[$method]))
            $this->routes[$method] = [];
        $this->routes[$method][$url] = $function;
    }

    private static function getRegex($url)
    {
        $quotedQuery = preg_quote(trim($url, '/'), '/');
        return '/^' . preg_replace('/\\\?\:([a-zA-Z_-]*)/', '(?P<\1>[^\/]+)', $quotedQuery) . '$/i';
    }
}
