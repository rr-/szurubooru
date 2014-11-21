<?php
namespace Szurubooru\Routes\Posts;
use Szurubooru\Controllers\ViewProxies\PostViewProxy;
use Szurubooru\FormData\UploadFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

class CreatePost extends AbstractPostRoute
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
		$this->inputReader = $inputReader;
		$this->postViewProxy = $postViewProxy;
	}

	public function getMethods()
	{
		return ['POST'];
	}

	public function getUrl()
	{
		return '/api/posts';
	}

	public function work()
	{
		$this->privilegeService->assertPrivilege(Privilege::UPLOAD_POSTS);
		$formData = new UploadFormData($this->inputReader);

		$this->privilegeService->assertPrivilege(Privilege::UPLOAD_POSTS);

		if ($formData->anonymous)
			$this->privilegeService->assertPrivilege(Privilege::UPLOAD_POSTS_ANONYMOUSLY);

		$post = $this->postService->createPost($formData);
		return $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig());
	}
}
