<?php
namespace Szurubooru\Controllers;

class CommentController extends AbstractController
{
	private $privilegeService;
	private $authService;
	private $postService;
	private $commentService;
	private $commentViewProxy;
	private $postViewProxy;
	private $inputReader;

	public function __construct(
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\PostService $postService,
		\Szurubooru\Services\CommentService $commentService,
		\Szurubooru\Controllers\ViewProxies\CommentViewProxy $commentViewProxy,
		\Szurubooru\Controllers\ViewProxies\PostViewProxy $postViewProxy,
		\Szurubooru\Helpers\InputReader $inputReader)
	{
		$this->privilegeService = $privilegeService;
		$this->authService = $authService;
		$this->postService = $postService;
		$this->commentService = $commentService;
		$this->commentViewProxy = $commentViewProxy;
		$this->postViewProxy = $postViewProxy;
		$this->inputReader = $inputReader;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->get('/api/comments', [$this, 'getComments']);
		$router->get('/api/comments/:postNameOrId', [$this, 'getPostComments']);
		$router->post('/api/comments/:postNameOrId', [$this, 'addComment']);
		$router->put('/api/comments/:commentId', [$this, 'editComment']);
		$router->delete('/api/comments/:commentId', [$this, 'deleteComment']);
	}

	public function getComments()
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::LIST_COMMENTS);

		$filter = new \Szurubooru\SearchServices\Filters\PostFilter();
		$filter->setPageSize(10);
		$filter->setPageNumber($this->inputReader->page);
		$filter->setOrder([
			\Szurubooru\SearchServices\Filters\PostFilter::ORDER_LAST_COMMENT_TIME =>
			\Szurubooru\SearchServices\Filters\PostFilter::ORDER_DESC]);

		$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
		$requirement->setValue(new \Szurubooru\SearchServices\Requirements\RequirementRangedValue());
		$requirement->getValue()->setMinValue(1);
		$requirement->setType(\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_COMMENT_COUNT);
		$filter->addRequirement($requirement);

		$result = $this->postService->getFiltered($filter);
		$posts = $result->getEntities();

		$data = [];
		foreach ($posts as $post)
		{
			$data[] = [
				'post' => $this->postViewProxy->fromEntity($post),
				'comments' => $this->commentViewProxy->fromArray($this->commentService->getByPost($post))];
		}

		return [
			'data' => $data,
			'pageSize' => $result->getPageSize(),
			'totalRecords' => $result->getTotalRecords()];
	}

	public function getPostComments($postNameOrId)
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::LIST_COMMENTS);
		$post = $this->postService->getByNameOrId($postNameOrId);

		$filter = new \Szurubooru\SearchServices\Filters\CommentFilter();
		$filter->setOrder([
			\Szurubooru\SearchServices\Filters\CommentFilter::ORDER_ID =>
			\Szurubooru\SearchServices\Filters\CommentFilter::ORDER_ASC]);

		$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
		$requirement->setValue(new \Szurubooru\SearchServices\Requirements\RequirementSingleValue($post->getId()));
		$requirement->setType(\Szurubooru\SearchServices\Filters\CommentFilter::REQUIREMENT_POST_ID);
		$filter->addRequirement($requirement);

		$result = $this->commentService->getFiltered($filter);
		$entities = $this->commentViewProxy->fromArray($result->getEntities());
		return ['data' => $entities];
	}

	public function addComment($postNameOrId)
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::ADD_COMMENTS);

		$post = $this->postService->getByNameOrId($postNameOrId);
		$comment = $this->commentService->createComment($post, $this->inputReader->text);
		return $this->commentViewProxy->fromEntity($comment);
	}

	public function editComment($commentId)
	{
		$comment = $this->commentService->getById($commentId);

		$this->privilegeService->assertPrivilege(
			($comment->getUser() and $this->privilegeService->isLoggedIn($comment->getUser()))
				? \Szurubooru\Privilege::EDIT_OWN_COMMENTS
				: \Szurubooru\Privilege::EDIT_ALL_COMMENTS);

		$comment = $this->commentService->updateComment($comment, $this->inputReader->text);
		return $this->commentViewProxy->fromEntity($comment);
	}

	public function deleteComment($commentId)
	{
		$comment = $this->commentService->getById($commentId);

		$this->privilegeService->assertPrivilege(
			$this->privilegeService->isLoggedIn($comment->getUser())
				? \Szurubooru\Privilege::DELETE_OWN_COMMENTS
				: \Szurubooru\Privilege::DELETE_ALL_COMMENTS);

		return $this->commentService->deleteComment($comment);
	}
}
