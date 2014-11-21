<?php
namespace Szurubooru\Routes\Posts;
use Szurubooru\Controllers\ViewProxies\PostViewProxy;
use Szurubooru\FormData\PostEditFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

class UpdatePost extends AbstractPostRoute
{
	private $privilegeService;
	private $postService;
	private $inputReader;
	private $postViewProxy;

	public function __construct(
		PrivilegeService $privilegeService,
		PostService $postService,
		InputReader $inputReader,
		PostViewProxy $postViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->postService = $postService;
	}

	public function getMethods()
	{
		return ['PUT'];
	}

	public function getUrl()
	{
		return '/api/posts/:postNameOrId';
	}

	public function work()
	{
		$postNameOrId = $this->getArgument('postNameOrId');
		$post = $this->postService->getByNameOrId($postNameOrId);
		$formData = new PostEditFormData($this->inputReader);

		if ($formData->content !== null)
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_POST_CONTENT);

		if ($formData->thumbnail !== null)
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_POST_THUMBNAIL);

		if ($formData->safety !== null)
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_POST_SAFETY);

		if ($formData->source !== null)
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_POST_SOURCE);

		if ($formData->tags !== null)
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_POST_TAGS);

		$this->postService->updatePost($post, $formData);
		$post = $this->postService->getByNameOrId($postNameOrId);
		return $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig());
	}
}
