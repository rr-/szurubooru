<?php
require_once('..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'AutoLoader.php');

function injectControllers($router)
{
	\Szurubooru\Controllers\AuthController::register($router);
}

$router = new \Szurubooru\Router;
injectControllers($router);

try
{
	$json = $router->handle($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
}
catch (\Exception $e)
{
	$json = [
		'error' => $e->getMessage(),
		'trace' => $e->getTrace(),
	];
}

header('Content-Type: application/json');
echo json_encode($json);
