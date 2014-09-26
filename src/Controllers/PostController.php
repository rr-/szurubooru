<?php
namespace Szurubooru\Controllers;

final class PostController extends AbstractController
{
	private $config;
	private $privilegeService;
	private $postService;
	private $postSearchParser;
	private $inputReader;
	private $postViewProxy;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\PostService $postService,
		\Szurubooru\SearchServices\Parsers\PostSearchParser $postSearchParser,
		\Szurubooru\Helpers\InputReader $inputReader,
		\Szurubooru\Controllers\ViewProxies\PostViewProxy $postViewProxy)
	{
		$this->config = $config;
		$this->privilegeService = $privilegeService;
		$this->postService = $postService;
		$this->postSearchParser = $postSearchParser;
		$this->inputReader = $inputReader;
		$this->postViewProxy = $postViewProxy;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->post('/api/posts', [$this, 'createPost']);
		$router->get('/api/posts', [$this, 'getFiltered']);
		$router->get('/api/posts/featured', [$this, 'getFeatured']);
		$router->get('/api/posts/:postNameOrId', [$this, 'getByNameOrId']);
		$router->put('/api/posts/:postNameOrId', [$this, 'updatePost']);
		$router->delete('/api/posts/:postNameOrId', [$this, 'deletePost']);
		$router->post('/api/posts/:postNameOrId/feature', [$this, 'featurePost']);
		$router->put('/api/posts/:postNameOrId/feature', [$this, 'featurePost']);
	}

	public function getFeatured()
	{
		$post = $this->postService->getFeatured();
		return $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig());
	}

	public function getByNameOrId($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);
		return $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig());
	}

	public function getFiltered()
	{
		$filter = $this->postSearchParser->createFilterFromInputReader($this->inputReader);
		$filter->setPageSize($this->config->posts->postsPerPage);
		$result = $this->postService->getFiltered($filter);
		$entities = $this->postViewProxy->fromArray($result->getEntities(), $this->getLightFetchConfig());
		return [
			'data' => $entities,
			'pageSize' => $result->getPageSize(),
			'totalRecords' => $result->getTotalRecords()];
	}

	public function createPost()
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::UPLOAD_POSTS);
		$formData = new \Szurubooru\FormData\UploadFormData($this->inputReader);

		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::UPLOAD_POSTS);

		if ($formData->anonymous)
			$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::UPLOAD_POSTS_ANONYMOUSLY);

		$post = $this->postService->createPost($formData);
		return $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig());
	}

	public function updatePost($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);
		$formData = new \Szurubooru\FormData\PostEditFormData($this->inputReader);

		if ($formData->content !== null)
			$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::CHANGE_POST_CONTENT);

		if ($formData->thumbnail !== null)
			$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::CHANGE_POST_THUMBNAIL);

		if ($formData->safety !== null)
			$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::CHANGE_POST_SAFETY);

		if ($formData->source !== null)
			$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::CHANGE_POST_SOURCE);

		if ($formData->tags !== null)
			$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::CHANGE_POST_TAGS);

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
		$this->postService->featurePost($post);
	}

	private function getFullFetchConfig()
	{
		return
		[
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_RELATIONS => true,
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_TAGS => true,
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_USER => true,
		];
	}

	private function getLightFetchConfig()
	{
		return
		[
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_TAGS => true,
		];
	}
}
