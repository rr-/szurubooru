<?php
namespace Szurubooru\Controllers;
use Szurubooru\Controllers\ViewProxies\CommentViewProxy;
use Szurubooru\Controllers\ViewProxies\PostViewProxy;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Router;
use Szurubooru\SearchServices\Filters\CommentFilter;
use Szurubooru\SearchServices\Filters\PostFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\SearchServices\Requirements\RequirementRangedValue;
use Szurubooru\SearchServices\Requirements\RequirementSingleValue;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\CommentService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

final class CommentController extends AbstractController
{
	private $privilegeService;
	private $authService;
	private $postService;
	private $commentService;
	private $commentViewProxy;
	private $postViewProxy;
	private $inputReader;

	public function __construct(
		PrivilegeService $privilegeService,
		AuthService $authService,
		PostService $postService,
		CommentService $commentService,
		CommentViewProxy $commentViewProxy,
		PostViewProxy $postViewProxy,
		InputReader $inputReader)
	{
		$this->privilegeService = $privilegeService;
		$this->authService = $authService;
		$this->postService = $postService;
		$this->commentService = $commentService;
		$this->commentViewProxy = $commentViewProxy;
		$this->postViewProxy = $postViewProxy;
		$this->inputReader = $inputReader;
	}

	public function registerRoutes(Router $router)
	{
		$router->get('/api/comments', [$this, 'getComments']);
		$router->get('/api/comments/:postNameOrId', [$this, 'getPostComments']);
		$router->post('/api/comments/:postNameOrId', [$this, 'addComment']);
		$router->put('/api/comments/:commentId', [$this, 'editComment']);
		$router->delete('/api/comments/:commentId', [$this, 'deleteComment']);
	}

	public function getComments()
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_COMMENTS);

		$filter = new PostFilter();
		$filter->setPageSize(10);
		$filter->setPageNumber($this->inputReader->page);
		$filter->setOrder([
			PostFilter::ORDER_LAST_COMMENT_TIME =>
			PostFilter::ORDER_DESC]);

		$this->postService->decorateFilterFromBrowsingSettings($filter);

		$requirement = new Requirement();
		$requirement->setValue(new RequirementRangedValue());
		$requirement->getValue()->setMinValue(1);
		$requirement->setType(PostFilter::REQUIREMENT_COMMENT_COUNT);
		$filter->addRequirement($requirement);

		$result = $this->postService->getFiltered($filter);
		$posts = $result->getEntities();

		$data = [];
		foreach ($posts as $post)
		{
			$data[] = [
				'post' => $this->postViewProxy->fromEntity($post),
				'comments' => $this->commentViewProxy->fromArray(
					array_reverse($this->commentService->getByPost($post)),
					$this->getCommentsFetchConfig()),
			];
		}

		return [
			'data' => $data,
			'pageSize' => $result->getPageSize(),
			'totalRecords' => $result->getTotalRecords()];
	}

	public function getPostComments($postNameOrId)
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_COMMENTS);
		$post = $this->postService->getByNameOrId($postNameOrId);

		$filter = new CommentFilter();
		$filter->setOrder([
			CommentFilter::ORDER_ID =>
			CommentFilter::ORDER_ASC]);

		$requirement = new Requirement();
		$requirement->setValue(new RequirementSingleValue($post->getId()));
		$requirement->setType(CommentFilter::REQUIREMENT_POST_ID);
		$filter->addRequirement($requirement);

		$result = $this->commentService->getFiltered($filter);
		$entities = $this->commentViewProxy->fromArray($result->getEntities(), $this->getCommentsFetchConfig());
		return ['data' => $entities];
	}

	public function addComment($postNameOrId)
	{
		$this->privilegeService->assertPrivilege(Privilege::ADD_COMMENTS);

		$post = $this->postService->getByNameOrId($postNameOrId);
		$comment = $this->commentService->createComment($post, $this->inputReader->text);
		return $this->commentViewProxy->fromEntity($comment, $this->getCommentsFetchConfig());
	}

	public function editComment($commentId)
	{
		$comment = $this->commentService->getById($commentId);

		$this->privilegeService->assertPrivilege(
			($comment->getUser() && $this->privilegeService->isLoggedIn($comment->getUser()))
				? Privilege::EDIT_OWN_COMMENTS
				: Privilege::EDIT_ALL_COMMENTS);

		$comment = $this->commentService->updateComment($comment, $this->inputReader->text);
		return $this->commentViewProxy->fromEntity($comment, $this->getCommentsFetchConfig());
	}

	public function deleteComment($commentId)
	{
		$comment = $this->commentService->getById($commentId);

		$this->privilegeService->assertPrivilege(
			$this->privilegeService->isLoggedIn($comment->getUser())
				? Privilege::DELETE_OWN_COMMENTS
				: Privilege::DELETE_ALL_COMMENTS);

		return $this->commentService->deleteComment($comment);
	}

	private function getCommentsFetchConfig()
	{
		return
		[
			CommentViewProxy::FETCH_OWN_SCORE => true,
		];
	}
}
