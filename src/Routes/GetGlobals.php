<?php
namespace Szurubooru\Routes;
use Szurubooru\Dao\GlobalParamDao;

class GetGlobals extends AbstractRoute
{
	private $globalParamDao;

	public function __construct(GlobalParamDao $globalParamDao)
	{
		$this->globalParamDao = $globalParamDao;
	}

	public function getMethods()
	{
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/globals';
	}

	public function work()
	{
		$globals = $this->globalParamDao->findAll();
		$result = [];
		foreach ($globals as $global)
		{
			$result[$global->getKey()] = $global->getValue();
		}
		return $result;
	}
}
