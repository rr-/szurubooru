<?php
namespace Szurubooru;

final class Dispatcher
{
	private $router;

	public function __construct(
		\Szurubooru\Router $router,
		\Szurubooru\Helpers\HttpHelper $httpHelper,
		\Szurubooru\ControllerRepository $controllerRepository)
	{
		$this->router = $router;
		$this->httpHelper = $httpHelper;

		//if script fails prematurely, mark it as fail from advance
		$this->httpHelper->setResponseCode(500);

		foreach ($controllerRepository->getControllers() as $controller)
			$controller->registerRoutes($router);
	}

	public function run()
	{
		global $start;
		try
		{
			$code = 200;
			$json = (array) $this->router->handle(
				$this->httpHelper->getRequestMethod(),
				$this->httpHelper->getRequestUri());
		}
		catch (\Exception $e)
		{
			$code = 400;
			$json = [
				'error' => $e->getMessage(),
				'trace' => $e->getTrace(),
			];
		}
		$end = microtime(true);
		$json['__time'] = $end - $start;

		$this->httpHelper->setResponseCode($code);
		$this->httpHelper->setHeader('Content-Type', 'application/json');
		$this->httpHelper->outputJSON($json);

		return $json;
	}
}
