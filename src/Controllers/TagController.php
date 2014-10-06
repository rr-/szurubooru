<?php
namespace Szurubooru\Controllers;

final class TagController extends AbstractController
{
	private $privilegeService;
	private $tagService;
	private $tagViewProxy;
	private $tagSearchParser;
	private $inputReader;

	public function __construct(
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\TagService $tagService,
		\Szurubooru\Controllers\ViewProxies\TagViewProxy $tagViewProxy,
		\Szurubooru\SearchServices\Parsers\TagSearchParser $tagSearchParser,
		\Szurubooru\Helpers\InputReader $inputReader)
	{
		$this->privilegeService = $privilegeService;
		$this->tagService = $tagService;
		$this->tagViewProxy = $tagViewProxy;
		$this->tagSearchParser = $tagSearchParser;
		$this->inputReader = $inputReader;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->get('/api/tags', [$this, 'getTags']);
	}

	public function getTags()
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::LIST_TAGS);

		$filter = $this->tagSearchParser->createFilterFromInputReader($this->inputReader);
		$filter->setPageSize(50);

		$result = $this->tagService->getFiltered($filter);
		$entities = $this->tagViewProxy->fromArray($result->getEntities());
		return [
			'data' => $entities,
			'pageSize' => $result->getPageSize(),
			'totalRecords' => $result->getTotalRecords()];
	}
}
