<?php
namespace Szurubooru\Routes\Tags;
use Szurubooru\Controllers\ViewProxies\TagViewProxy;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TagService;

class GetTag extends AbstractTagRoute
{
	private $privilegeService;
	private $tagService;
	private $tagViewProxy;

	public function __construct(
		PrivilegeService $privilegeService,
		TagService $tagService,
		TagViewProxy $tagViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->tagService = $tagService;
		$this->tagViewProxy = $tagViewProxy;
	}

	public function getMethods()
	{
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/tags/:tagName';
	}

	public function work($args)
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_TAGS);

		$tag = $this->tagService->getByName($args['tagName']);
		return $this->tagViewProxy->fromEntity($tag, $this->getFullFetchConfig());
	}
}
