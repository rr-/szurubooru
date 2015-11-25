<?php
namespace Szurubooru\Routes\Posts;
use Szurubooru\Config;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Search\SearchParser;
use Szurubooru\Search\ParserConfigs\PostSearchParserConfig;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\ViewProxies\PostViewProxy;

class GetPosts extends AbstractPostRoute
{
    private $config;
    private $privilegeService;
    private $postService;
    private $searchParser;
    private $inputReader;
    private $postViewProxy;

    public function __construct(
        Config $config,
        PrivilegeService $privilegeService,
        PostService $postService,
        PostSearchParserConfig $searchParserConfig,
        InputReader $inputReader,
        PostViewProxy $postViewProxy)
    {
        $this->config = $config;
        $this->privilegeService = $privilegeService;
        $this->postService = $postService;
        $this->searchParser = new SearchParser($searchParserConfig);
        $this->inputReader = $inputReader;
        $this->postViewProxy = $postViewProxy;
    }

    public function getMethods()
    {
        return ['GET'];
    }

    public function getUrl()
    {
        return '/api/posts';
    }

    public function work($args)
    {
        $this->privilegeService->assertPrivilege(Privilege::LIST_POSTS);

        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
        $filter->setPageSize($this->config->posts->postsPerPage);
        $this->postService->decorateFilterFromBrowsingSettings($filter);

        $result = $this->postService->getFiltered($filter);
        $entities = $this->postViewProxy->fromArray($result->getEntities(), $this->getLightFetchConfig());
        return [
            'posts' => $entities,
            'pageSize' => $result->getPageSize(),
            'totalRecords' => $result->getTotalRecords()];
    }
}
