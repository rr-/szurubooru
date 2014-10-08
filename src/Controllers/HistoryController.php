<?php
namespace Szurubooru\Controllers;
use Szurubooru\Controllers\ViewProxies\SnapshotViewProxy;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Router;
use Szurubooru\SearchServices\Parsers\SnapshotSearchParser;
use Szurubooru\Services\HistoryService;
use Szurubooru\Services\PrivilegeService;

final class HistoryController extends AbstractController
{
	private $historyService;
	private $privilegeService;
	private $snapshotSearchParser;
	private $inputReader;
	private $snapshotViewProxy;

	public function __construct(
		HistoryService $historyService,
		PrivilegeService $privilegeService,
		SnapshotSearchParser $snapshotSearchParser,
		InputReader $inputReader,
		SnapshotViewProxy $snapshotViewProxy)
	{
		$this->historyService = $historyService;
		$this->privilegeService = $privilegeService;
		$this->snapshotSearchParser = $snapshotSearchParser;
		$this->inputReader = $inputReader;
		$this->snapshotViewProxy = $snapshotViewProxy;
	}

	public function registerRoutes(Router $router)
	{
		$router->get('/api/history', [$this, 'getFiltered']);
	}

	public function getFiltered()
	{
		$this->privilegeService->assertPrivilege(Privilege::VIEW_HISTORY);

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
