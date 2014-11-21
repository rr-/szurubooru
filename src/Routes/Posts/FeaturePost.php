<?php
namespace Szurubooru\Routes\Posts;
use Szurubooru\Privilege;
use Szurubooru\Services\PostFeatureService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

class FeaturePost extends AbstractPostRoute
{
	private $privilegeService;
	private $postService;
	private $postFeatureService;

	public function __construct(
		PrivilegeService $privilegeService,
		PostService $postService,
		PostFeatureService $postFeatureService)
	{
		$this->privilegeService = $privilegeService;
		$this->postService = $postService;
		$this->postFeatureService = $postFeatureService;
	}

	public function getMethods()
	{
		return ['POST', 'PUT'];
	}

	public function getUrl()
	{
		return '/api/posts/:postNameOrId/feature';
	}

	public function work()
	{
		$this->privilegeService->assertPrivilege(Privilege::FEATURE_POSTS);

		$post = $this->postService->getByNameOrId($this->getArgument('postNameOrId'));
		$this->postFeatureService->featurePost($post);
	}
}
