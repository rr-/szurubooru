<?php
namespace Szurubooru\Controllers;
use Szurubooru\Dao\GlobalParamDao;
use Szurubooru\Router;

final class GlobalParamController extends AbstractController
{
	private $globalParamDao;

	public function __construct(GlobalParamDao $globalParamDao)
	{
		$this->globalParamDao = $globalParamDao;
	}

	public function registerRoutes(Router $router)
	{
		$router->get('/api/globals', [$this, 'getGlobals']);
	}

	public function getGlobals()
	{
		$globals = $this->globalParamDao->findAll();
		$return = [];
		foreach ($globals as $global)
		{
			$return[$global->getKey()] = $global->getValue();
		}
		return $return;
	}
}
