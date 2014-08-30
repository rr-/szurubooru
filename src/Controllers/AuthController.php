<?php
namespace Szurubooru\Controllers;

final class AuthController extends AbstractController
{
	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->post('/api/login', [$this, 'login']);
		$router->get('/api/login', [$this, 'login']);
	}

	public function login()
	{
	}
}
