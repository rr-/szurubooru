<?php
namespace Szurubooru\Controllers;

final class GlobalParamController extends AbstractController
{
	private $globalParamDao;

	public function __construct(
		\Szurubooru\Dao\GlobalParamDao $globalParamDao)
	{
		$this->globalParamDao = $globalParamDao;
	}

	public function registerRoutes(\Szurubooru\Router $router)
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
