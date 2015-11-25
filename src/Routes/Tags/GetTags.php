<?php
namespace Szurubooru\Routes\Tags;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Search\ParserConfigs\TagSearchParserConfig;
use Szurubooru\Search\SearchParser;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TagService;
use Szurubooru\ViewProxies\TagViewProxy;

class GetTags extends AbstractTagRoute
{
    private $privilegeService;
    private $tagService;
    private $tagViewProxy;
    private $searchParserConfig;
    private $inputReader;

    public function __construct(
        PrivilegeService $privilegeService,
        TagService $tagService,
        TagViewProxy $tagViewProxy,
        TagSearchParserConfig $searchParserConfig,
        InputReader $inputReader)
    {
        $this->privilegeService = $privilegeService;
        $this->tagService = $tagService;
        $this->tagViewProxy = $tagViewProxy;
        $this->searchParser = new SearchParser($searchParserConfig);
        $this->inputReader = $inputReader;
    }

    public function getMethods()
    {
        return ['GET'];
    }

    public function getUrl()
    {
        return '/api/tags';
    }

    public function work($args)
    {
        $this->privilegeService->assertPrivilege(Privilege::LIST_TAGS);

        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
        $filter->setPageSize(50);

        $result = $this->tagService->getFiltered($filter);
        $entities = $this->tagViewProxy->fromArray($result->getEntities(), $this->getFullFetchConfig());
        return [
            'tags' => $entities,
            'pageSize' => $result->getPageSize(),
            'totalRecords' => $result->getTotalRecords()];
    }
}
