<?php
namespace Szurubooru\Controllers;

final class PostController extends AbstractController
{
	private $config;
	private $authService;
	private $privilegeService;
	private $postService;
	private $postSearchParser;
	private $inputReader;
	private $postViewProxy;
	private $snapshotViewProxy;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\PostService $postService,
		\Szurubooru\SearchServices\Parsers\PostSearchParser $postSearchParser,
		\Szurubooru\Helpers\InputReader $inputReader,
		\Szurubooru\Controllers\ViewProxies\PostViewProxy $postViewProxy,
		\Szurubooru\Controllers\ViewProxies\SnapshotViewProxy $snapshotViewProxy)
	{
		$this->config = $config;
		$this->authService = $authService;
		$this->privilegeService = $privilegeService;
		$this->postService = $postService;
		$this->postSearchParser = $postSearchParser;
		$this->inputReader = $inputReader;
		$this->postViewProxy = $postViewProxy;
		$this->snapshotViewProxy = $snapshotViewProxy;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->post('/api/posts', [$this, 'createPost']);
		$router->get('/api/posts', [$this, 'getFiltered']);
		$router->get('/api/posts/:postNameOrId', [$this, 'getByNameOrId']);
		$router->get('/api/posts/:postNameOrId/history', [$this, 'getHistory']);
		$router->put('/api/posts/:postNameOrId', [$this, 'updatePost']);
		$router->delete('/api/posts/:postNameOrId', [$this, 'deletePost']);
		$router->post('/api/posts/:postNameOrId/feature', [$this, 'featurePost']);
		$router->put('/api/posts/:postNameOrId/feature', [$this, 'featurePost']);
	}

	public function getByNameOrId($postNameOrId)
	{
		if ($postNameOrId !== 'featured')
			$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::VIEW_POSTS);

		$post = $this->getByNameOrIdWithoutProxy($postNameOrId);
		return $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig());
	}

	public function getHistory($postNameOrId)
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::VIEW_HISTORY);
		$post = $this->getByNameOrIdWithoutProxy($postNameOrId);
		return ['data' => $this->snapshotViewProxy->fromArray($this->postService->getHistory($post))];
	}

	public function getFiltered()
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::LIST_POSTS);

		$filter = $this->postSearchParser->createFilterFromInputReader($this->inputReader);
		$filter->setPageSize($this->config->posts->postsPerPage);
		$this->decorateFilterFromBrowsingSettings($filter);

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

	private function getByNameOrIdWithoutProxy($postNameOrId)
	{
		if ($postNameOrId === 'featured')
			return $this->postService->getFeatured();
		else
			return $this->postService->getByNameOrId($postNameOrId);
	}

	private function getFullFetchConfig()
	{
		return
		[
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_RELATIONS => true,
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_TAGS => true,
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_USER => true,
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_HISTORY => true,
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_OWN_SCORE => true,
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_FAVORITES => true,
		];
	}

	private function getLightFetchConfig()
	{
		return
		[
			\Szurubooru\Controllers\ViewProxies\PostViewProxy::FETCH_TAGS => true,
		];
	}

	private function decorateFilterFromBrowsingSettings($filter)
	{
		$currentUser = $this->authService->getLoggedInUser();
		$userSettings = $currentUser->getBrowsingSettings();
		if (!$userSettings)
			return;

		if (!empty($userSettings->listPosts) and !count($filter->getRequirementsByType(\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_SAFETY)))
		{
			$values = [];
			if (!\Szurubooru\Helpers\TypeHelper::toBool($userSettings->listPosts->safe))
				$values[] = \Szurubooru\Entities\Post::POST_SAFETY_SAFE;
			if (!\Szurubooru\Helpers\TypeHelper::toBool($userSettings->listPosts->sketchy))
				$values[] = \Szurubooru\Entities\Post::POST_SAFETY_SKETCHY;
			if (!\Szurubooru\Helpers\TypeHelper::toBool($userSettings->listPosts->unsafe))
				$values[] = \Szurubooru\Entities\Post::POST_SAFETY_UNSAFE;
			if (count($values))
			{
				$requirementValue = new \Szurubooru\SearchServices\Requirements\RequirementCompositeValue();
				$requirementValue->setValues($values);
				$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
				$requirement->setType(\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_SAFETY);
				$requirement->setValue($requirementValue);
				$requirement->setNegated(true);
				$filter->addRequirement($requirement);
			}
		}

		if (!empty($userSettings->hideDownvoted) and !count($filter->getRequirementsByType(\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_USER_SCORE)))
		{
			$requirementValue = new \Szurubooru\SearchServices\Requirements\RequirementCompositeValue();
			$requirementValue->setValues([$currentUser->getName(), -1]);
			$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
			$requirement->setType(\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_USER_SCORE);
			$requirement->setValue($requirementValue);
			$requirement->setNegated(true);
			$filter->addRequirement($requirement);
		}
	}
}
