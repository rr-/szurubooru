<?php
namespace Szurubooru\Routes\Tags;
use Szurubooru\Controllers\ViewProxies\TagViewProxy;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\SearchServices\Parsers\TagSearchParser;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TagService;

class GetTags extends AbstractTagRoute
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

	public function getMethods()
	{
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/tags';
	}

	public function work()
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
}
