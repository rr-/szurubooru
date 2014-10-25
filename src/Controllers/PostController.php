<?php
namespace Szurubooru\Controllers;
use Szurubooru\Config;
use Szurubooru\Controllers\ViewProxies\PostViewProxy;
use Szurubooru\Controllers\ViewProxies\SnapshotViewProxy;
use Szurubooru\Controllers\ViewProxies\UserViewProxy;
use Szurubooru\Entities\Post;
use Szurubooru\FormData\PostEditFormData;
use Szurubooru\FormData\UploadFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Router;
use Szurubooru\SearchServices\Parsers\PostSearchParser;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PostFeatureService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

final class PostController extends AbstractController
{
	private $config;
	private $authService;
	private $privilegeService;
	private $postService;
	private $postFeatureService;
	private $postSearchParser;
	private $inputReader;
	private $postViewProxy;
	private $snapshotViewProxy;

	public function __construct(
		Config $config,
		AuthService $authService,
		PrivilegeService $privilegeService,
		PostService $postService,
		PostFeatureService $postFeatureService,
		PostSearchParser $postSearchParser,
		InputReader $inputReader,
		UserViewProxy $userViewProxy,
		PostViewProxy $postViewProxy,
		SnapshotViewProxy $snapshotViewProxy)
	{
		$this->config = $config;
		$this->authService = $authService;
		$this->privilegeService = $privilegeService;
		$this->postService = $postService;
		$this->postFeatureService = $postFeatureService;
		$this->postSearchParser = $postSearchParser;
		$this->inputReader = $inputReader;
		$this->userViewProxy = $userViewProxy;
		$this->postViewProxy = $postViewProxy;
		$this->snapshotViewProxy = $snapshotViewProxy;
	}

	public function registerRoutes(Router $router)
	{
		$router->post('/api/posts', [$this, 'createPost']);
		$router->get('/api/posts', [$this, 'getFiltered']);
		$router->get('/api/posts/featured', [$this, 'getFeatured']);
		$router->get('/api/posts/:postNameOrId', [$this, 'getByNameOrId']);
		$router->get('/api/posts/:postNameOrId/history', [$this, 'getHistory']);
		$router->put('/api/posts/:postNameOrId', [$this, 'updatePost']);
		$router->delete('/api/posts/:postNameOrId', [$this, 'deletePost']);
		$router->post('/api/posts/:postNameOrId/feature', [$this, 'featurePost']);
		$router->put('/api/posts/:postNameOrId/feature', [$this, 'featurePost']);
	}

	public function getFeatured()
	{
		$post = $this->postFeatureService->getFeaturedPost();
		$user = $this->postFeatureService->getFeaturedPostUser();
		return [
			'user' => $this->userViewProxy->fromEntity($user),
			'post' => $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig()),
		];
	}

	public function getByNameOrId($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);
		return $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig());
	}

	public function getHistory($postNameOrId)
	{
		$this->privilegeService->assertPrivilege(Privilege::VIEW_HISTORY);
		$post = $this->getByNameOrId($postNameOrId);
		return ['data' => $this->snapshotViewProxy->fromArray($this->postService->getHistory($post))];
	}

	public function getFiltered()
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_POSTS);

		$filter = $this->postSearchParser->createFilterFromInputReader($this->inputReader);
		$filter->setPageSize($this->config->posts->postsPerPage);
		$this->postService->decorateFilterFromBrowsingSettings($filter);

		$result = $this->postService->getFiltered($filter);
		$entities = $this->postViewProxy->fromArray($result->getEntities(), $this->getLightFetchConfig());
		return [
			'data' => $entities,
			'pageSize' => $result->getPageSize(),
			'totalRecords' => $result->getTotalRecords()];
	}

	public function createPost()
	{
		$this->privilegeService->assertPrivilege(Privilege::UPLOAD_POSTS);
		$formData = new UploadFormData($this->inputReader);

		$this->privilegeService->assertPrivilege(Privilege::UPLOAD_POSTS);

		if ($formData->anonymous)
			$this->privilegeService->assertPrivilege(Privilege::UPLOAD_POSTS_ANONYMOUSLY);

		$post = $this->postService->createPost($formData);
		return $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig());
	}

	public function updatePost($postNameOrId)
	{
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

	public function deletePost($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);
		$this->postService->deletePost($post);
	}

	public function featurePost($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);
		$this->postFeatureService->featurePost($post);
	}

	private function getFullFetchConfig()
	{
		return
		[
			PostViewProxy::FETCH_RELATIONS => true,
			PostViewProxy::FETCH_TAGS => true,
			PostViewProxy::FETCH_USER => true,
			PostViewProxy::FETCH_HISTORY => true,
			PostViewProxy::FETCH_OWN_SCORE => true,
			PostViewProxy::FETCH_FAVORITES => true,
			PostViewProxy::FETCH_NOTES => true,
		];
	}

	private function getLightFetchConfig()
	{
		return
		[
			PostViewProxy::FETCH_TAGS => true,
		];
	}
}
