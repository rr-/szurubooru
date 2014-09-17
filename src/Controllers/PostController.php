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
		$router->get('/api/posts', [$this, 'getFiltered']);
		$router->post('/api/posts', [$this, 'createPost']);
	}

	public function getFiltered()
	{
		$formData = new \Szurubooru\FormData\SearchFormData($this->inputReader);
		$searchResult = $this->postService->getFiltered($formData);
		$entities = $this->postViewProxy->fromArray($searchResult->entities);
		return [
			'data' => $entities,
			'pageSize' => $searchResult->filter->pageSize,
			'totalRecords' => $searchResult->totalRecords];
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
}