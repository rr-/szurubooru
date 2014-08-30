<?php
namespace Szurubooru;

final class Dispatcher
{
	private $router;

	public function __construct(
		\Szurubooru\Router $router,
		\Szurubooru\ControllerRepository $controllerRepository)
	{
		$this->router = $router;
		foreach ($controllerRepository->getControllers() as $controller)
			$controller->registerRoutes($router);
	}

	public function run()
	{
		global $start;
		try
		{
			$json = $this->router->handle($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
		}
		catch (\Exception $e)
		{
			$json = [
				'error' => $e->getMessage(),
				'trace' => $e->getTrace(),
			];
		}
		$end = microtime(true);
		$json['__time'] = $end - $start;
		header('Content-Type: application/json');
		echo json_encode($json);
	}
}
