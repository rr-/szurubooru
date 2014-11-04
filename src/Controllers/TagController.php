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
		$router->get('/api/tags/:tagName/siblings', [$this, 'getTagSiblings']);
		$router->put('/api/tags/:tagName', [$this, 'updateTag']);
		$router->delete('/api/tags/:tagName', [$this, 'deleteTag']);
	}

	public function getTag($tagName)
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_TAGS);

		$tag = $this->tagService->getByName($tagName);
		return $this->tagViewProxy->fromEntity($tag, $this->getFullFetchConfig());
	}

	public function getTags()
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_TAGS);

		$filter = $this->tagSearchParser->createFilterFromInputReader($this->inputReader);
		$filter->setPageSize(50);

		$result = $this->tagService->getFiltered($filter);
		$entities = $this->tagViewProxy->fromArray($result->getEntities(), $this->getFullFetchConfig());
		return [
			'data' => $entities,
			'pageSize' => $result->getPageSize(),
			'totalRecords' => $result->getTotalRecords()];
	}

	public function getTagSiblings($tagName)
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_TAGS);
		$tag = $this->tagService->getByName($tagName);
		$result = $this->tagService->getSiblings($tagName);
		$entities = $this->tagViewProxy->fromArray($result);
		return [
			'data' => $entities,
		];
	}

	public function updateTag($tagName)
	{
		$tag = $this->tagService->getByName($tagName);
		$formData = new TagEditFormData($this->inputReader);

		if ($formData->name !== null)
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_TAG_NAME);

		if ($formData->category !== null)
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_TAG_CATEGORY);

		if ($formData->banned !== null)
			$this->privilegeService->assertPrivilege(Privilege::BAN_TAGS);

		if ($formData->implications !== null)
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_TAG_IMPLICATIONS);

		if ($formData->suggestions !== null)
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_TAG_SUGGESTIONS);

		$tag = $this->tagService->updateTag($tag, $formData);
		return $this->tagViewProxy->fromEntity($tag, $this->getFullFetchConfig());
	}

	public function deleteTag($tagName)
	{
		$tag = $this->tagService->getByName($tagName);
		$this->privilegeService->assertPrivilege(Privilege::DELETE_TAGS);
		return $this->tagService->deleteTag($tag);
	}

	private function getFullFetchConfig()
	{
		return
		[
			TagViewProxy::FETCH_IMPLICATIONS => true,
			TagViewProxy::FETCH_SUGGESTIONS => true,
		];
	}
}
