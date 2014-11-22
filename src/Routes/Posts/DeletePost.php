<?php
namespace Szurubooru\Routes\Posts;
use Szurubooru\Privilege;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

class DeletePost extends AbstractPostRoute
{
	private $privilegeService;
	private $postService;

	public function __construct(
		PrivilegeService $privilegeService,
		PostService $postService)
	{
		$this->privilegeService = $privilegeService;
		$this->postService = $postService;
	}

	public function getMethods()
	{
		return ['DELETE'];
	}

	public function getUrl()
	{
		return '/api/posts/:postNameOrId';
	}

	public function work($args)
	{
		$this->privilegeService->assertPrivilege(Privilege::DELETE_POSTS);

		$post = $this->postService->getByNameOrId($args['postNameOrId']);
		$this->postService->deletePost($post);
	}
}
