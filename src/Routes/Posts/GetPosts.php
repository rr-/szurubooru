<?php
namespace Szurubooru\Routes\Posts;
use Szurubooru\Config;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\SearchServices\Parsers\PostSearchParser;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\ViewProxies\PostViewProxy;

class GetPosts extends AbstractPostRoute
{
	private $config;
	private $privilegeService;
	private $postService;
	private $postSearchParser;
	private $inputReader;
	private $postViewProxy;

	public function __construct(
		Config $config,
		PrivilegeService $privilegeService,
		PostService $postService,
		PostSearchParser $postSearchParser,
		InputReader $inputReader,
		PostViewProxy $postViewProxy)
	{
		$this->config = $config;
		$this->privilegeService = $privilegeService;
		$this->postService = $postService;
		$this->postSearchParser = $postSearchParser;
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

		$filter = $this->postSearchParser->createFilterFromInputReader($this->inputReader);
		$filter->setPageSize($this->config->posts->postsPerPage);
		$this->postService->decorateFilterFromBrowsingSettings($filter);

		$result = $this->postService->getFiltered($filter);
		$entities = $this->postViewProxy->fromArray($result->getEntities(), $this->getLightFetchConfig());
		return [
			'data' => $entities,
			'pageSize' => $result->getPageSize(),
			'totalRecords' => $result->getTotalRecords()];
	}
}
