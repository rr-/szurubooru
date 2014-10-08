<?php
namespace Szurubooru;
use Szurubooru\Bootstrap;
use Szurubooru\Config;
use Szurubooru\ControllerRepository;
use Szurubooru\DatabaseConnection;
use Szurubooru\Helpers\HttpHelper;
use Szurubooru\Router;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\TokenService;

final class Dispatcher
{
	private $router;
	private $config;
	private $databaseConnection;
	private $authService;
	private $tokenService;

	public function __construct(
		Router $router,
		Config $config,
		DatabaseConnection $databaseConnection,
		HttpHelper $httpHelper,
		AuthService $authService,
		TokenService $tokenService,
		ControllerRepository $controllerRepository)
	{
		$this->router = $router;
		$this->config = $config;
		$this->databaseConnection = $databaseConnection;
		$this->httpHelper = $httpHelper;
		$this->authService = $authService;
		$this->tokenService = $tokenService;

		//if script fails prematurely, mark it as fail from advance
		$this->httpHelper->setResponseCode(500);

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
		$json['__time'] = $end - Bootstrap::getStartTime();
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
