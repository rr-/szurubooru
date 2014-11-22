<?php
namespace Szurubooru\Routes;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Routes\AbstractRoute;
use Szurubooru\SearchServices\Parsers\SnapshotSearchParser;
use Szurubooru\Services\HistoryService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\ViewProxies\SnapshotViewProxy;

class GetHistory extends AbstractRoute
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

	public function getMethods()
	{
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/history';
	}

	public function work($args)
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
