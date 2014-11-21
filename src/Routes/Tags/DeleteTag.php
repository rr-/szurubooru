<?php
namespace Szurubooru\Routes\Tags;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TagService;

class DeleteTag extends AbstractTagRoute
{
	private $privilegeService;
	private $tagService;

	public function __construct(
		PrivilegeService $privilegeService,
		TagService $tagService)
	{
		$this->privilegeService = $privilegeService;
		$this->tagService = $tagService;
	}

	public function getMethods()
	{
		return ['DELETE'];
	}

	public function getUrl()
	{
		return '/api/tags/:tagName';
	}

	public function work()
	{
		$tag = $this->tagService->getByName($this->getArgument('tagName'));
		$this->privilegeService->assertPrivilege(Privilege::DELETE_TAGS);
		return $this->tagService->deleteTag($tag);
	}
}
