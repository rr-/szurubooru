<?php
namespace Szurubooru\Controllers;
use Szurubooru\Controllers\ViewProxies\TagViewProxy;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Router;
use Szurubooru\SearchServices\Parsers\TagSearchParser;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TagService;

final class TagController extends AbstractController
{
	private $privilegeService;
	private $tagService;
	private $tagViewProxy;
	private $tagSearchParser;
	private $inputReader;

	public function __construct(
		PrivilegeService $privilegeService,
		TagService $tagService,
		TagViewProxy $tagViewProxy,
		TagSearchParser $tagSearchParser,
		InputReader $inputReader)
	{
		$this->privilegeService = $privilegeService;
		$this->tagService = $tagService;
		$this->tagViewProxy = $tagViewProxy;
		$this->tagSearchParser = $tagSearchParser;
		$this->inputReader = $inputReader;
	}

	public function registerRoutes(Router $router)
	{
		$router->get('/api/tags', [$this, 'getTags']);
	}

	public function getTags()
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_TAGS);

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
