<?php
namespace Szurubooru;

final class Dispatcher
{
	private $router;
	private $config;
	private $databaseConnection;
	private $authService;
	private $tokenService;

	public function __construct(
		\Szurubooru\Router $router,
		\Szurubooru\Config $config,
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Helpers\HttpHelper $httpHelper,
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\TokenService $tokenService,
		\Szurubooru\ControllerRepository $controllerRepository)
	{
		$this->router = $router;
		$this->config = $config;
		$this->databaseConnection = $databaseConnection;
		$this->httpHelper = $httpHelper;

		//if script fails prematurely, mark it as fail from advance
		$this->httpHelper->setResponseCode(500);
		$this->authService = $authService;
		$this->tokenService = $tokenService;

		foreach ($controllerRepository->getControllers() as $controller)
			$controller->registerRoutes($router);
	}

	public function run($requestMethod, $requestUri)
	{
		try
		{
			$code = 200;
			$this->authorizeFromRequestHeader();
			$json = (array) $this->router->handle($requestMethod, $requestUri);
		}
		catch (\Exception $e)
		{
			$code = 400;
			$trace = $e->getTrace();
			foreach ($trace as &$item)
				unset($item['args']);
			$json = [
				'error' => $e->getMessage(),
				'trace' => $trace,
			];
		}
		$end = microtime(true);
		$json['__time'] = $end - \Szurubooru\Bootstrap::getStartTime();
		if ($this->config->misc->dumpSqlIntoQueries)
		{
			$json['__queries'] = $this->databaseConnection->getPDO()->getQueryCount();
			$json['__statements'] = $this->databaseConnection->getPDO()->getStatements();
		}

		$this->httpHelper->setResponseCode($code);
		$this->httpHelper->setHeader('Content-Type', 'application/json');
		$this->httpHelper->outputJSON($json);

		return $json;
	}

	private function authorizeFromRequestHeader()
	{
		$loginTokenName = $this->httpHelper->getRequestHeader('X-Authorization-Token');
		if ($loginTokenName)
		{
			$token = $this->tokenService->getByName($loginTokenName);
			$this->authService->loginFromToken($token);
		}
	}
}
