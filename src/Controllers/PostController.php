<?php
namespace Szurubooru\Controllers;

final class PostController extends AbstractController
{
	private $privilegeService;
	private $postService;
	private $inputReader;
	private $postViewProxy;

	public function __construct(
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\PostService $postService,
		\Szurubooru\Helpers\InputReader $inputReader,
		\Szurubooru\Controllers\ViewProxies\PostViewProxy $postViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->postService = $postService;
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
		return $this->postViewProxy->fromEntity($post);
	}

	public function getByNameOrId($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);
		return $this->postViewProxy->fromEntity($post);
	}

	public function getFiltered()
	{
		$formData = new \Szurubooru\FormData\SearchFormData($this->inputReader);
		$searchResult = $this->postService->getFiltered($formData);
		$entities = $this->postViewProxy->fromArray($searchResult->getEntities());
		return [
			'data' => $entities,
			'pageSize' => $searchResult->getPageSize(),
			'totalRecords' => $searchResult->getTotalRecords()];
	}

	public function createPost()
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::UPLOAD_POSTS);
		$formData = new \Szurubooru\FormData\UploadFormData($this->inputReader);

		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::UPLOAD_POSTS);

		if ($formData->anonymous)
			$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::UPLOAD_POSTS_ANONYMOUSLY);

		$post = $this->postService->createPost($formData);
		return $this->postViewProxy->fromEntity($post);
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
		return $this->postViewProxy->fromEntity($post);
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
}
