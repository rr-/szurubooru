<?php
namespace Szurubooru\Routes;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Routes\AbstractRoute;
use Szurubooru\Search\ParserConfigs\SnapshotSearchParserConfig;
use Szurubooru\Search\SearchParser;
use Szurubooru\Services\HistoryService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\ViewProxies\SnapshotViewProxy;

class GetHistory extends AbstractRoute
{
    private $historyService;
    private $privilegeService;
    private $searchParser;
    private $inputReader;
    private $snapshotViewProxy;

    public function __construct(
        HistoryService $historyService,
        PrivilegeService $privilegeService,
        SnapshotSearchParserConfig $searchParserConfig,
        InputReader $inputReader,
        SnapshotViewProxy $snapshotViewProxy)
    {
        $this->historyService = $historyService;
        $this->privilegeService = $privilegeService;
        $this->searchParser = new SearchParser($searchParserConfig);
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

        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
        $filter->setPageSize(50);
        $result = $this->historyService->getFiltered($filter);
        $entities = $this->snapshotViewProxy->fromArray($result->getEntities());
        return [
            'history' => $entities,
            'pageSize' => $result->getPageSize(),
            'totalRecords' => $result->getTotalRecords()];
    }
}
