<?php
namespace Szurubooru\Controllers;

final class HistoryController extends AbstractController
{
	private $historyService;
	private $privilegeService;
	private $snapshotSearchParser;
	private $inputReader;
	private $snapshotViewProxy;

	public function __construct(
		\Szurubooru\Services\HistoryService $historyService,
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\SearchServices\Parsers\SnapshotSearchParser $snapshotSearchParser,
		\Szurubooru\Helpers\InputReader $inputReader,
		\Szurubooru\Controllers\ViewProxies\SnapshotViewProxy $snapshotViewProxy)
	{
		$this->historyService = $historyService;
		$this->privilegeService = $privilegeService;
		$this->snapshotSearchParser = $snapshotSearchParser;
		$this->inputReader = $inputReader;
		$this->snapshotViewProxy = $snapshotViewProxy;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->get('/api/history', [$this, 'getFiltered']);
	}

	public function getFiltered()
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::VIEW_HISTORY);

		$filter = $this->snapshotSearchParser->createFilterFromInputReader($this->inputReader);
		$filter->setPageSize(50);
		$result = $this->historyService->getFiltered($filter);
		$entities = $this->snapshotViewProxy->fromArray($result->getEntities());
		return [
			'data' => $entities,
			'pageSize' => $result->getPageSize(),
			'totalRecords' => $result->getTotalRecords()];
	}
}
