<?php
namespace Szurubooru\Controllers;
use Szurubooru\Controllers\ViewProxies\TagViewProxy;
use Szurubooru\FormData\TagEditFormData;
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
		$router->get('/api/tags/:tagName', [$this, 'getTag']);
		$router->put('/api/tags/:tagName', [$this, 'updateTag']);
	}

	public function getTag($tagName)
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_TAGS);

		$tag = $this->tagService->getByName($tagName);
		return $this->tagViewProxy->fromEntity($tag);
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

	public function updateTag($tagName)
	{
		$tag = $this->tagService->getByName($tagName);
		$formData = new TagEditFormData($this->inputReader);

		if ($formData->name !== null)
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_TAG_NAME);

		$tag = $this->tagService->updateTag($tag, $formData);
		return $this->tagViewProxy->fromEntity($tag);
	}
}
