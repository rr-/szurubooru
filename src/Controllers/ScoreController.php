<?php
namespace Szurubooru\Controllers;

class ScoreController extends AbstractController
{
	private $privilegeService;
	private $authService;
	private $postService;
	private $commentService;
	private $scoreService;
	private $inputReader;

	public function __construct(
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\PostService $postService,
		\Szurubooru\Services\CommentService $commentService,
		\Szurubooru\Services\ScoreService $scoreService,
		\Szurubooru\Helpers\InputReader $inputReader)
	{
		$this->privilegeService = $privilegeService;
		$this->authService = $authService;
		$this->postService = $postService;
		$this->commentService = $commentService;
		$this->scoreService = $scoreService;
		$this->inputReader = $inputReader;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->get('/api/posts/:postNameOrId/score', [$this, 'getPostScore']);
		$router->post('/api/posts/:postNameOrId/score', [$this, 'setPostScore']);
		$router->get('/api/comments/:commentId/score', [$this, 'getCommentScore']);
		$router->post('/api/comments/:commentId/score', [$this, 'setCommentScore']);
	}

	public function getPostScore($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);
		return $this->getScore($post);
	}

	public function setPostScore($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);
		return $this->setScore($post);
	}

	public function getCommentScore($commentId)
	{
		$comment = $this->commentService->getById($commentId);
		return $this->getScore($comment);
	}

	public function setCommentScore($commentId)
	{
		$comment = $this->commentService->getById($commentId);
		return $this->setScore($comment);
	}

	private function setScore(\Szurubooru\Entities\Entity $entity)
	{
		$this->privilegeService->assertLoggedIn();
		$score = intval($this->inputReader->score);
		$user = $this->authService->getLoggedInUser();
		$result = $this->scoreService->setScore($user, $entity, $score);
		return ['score' => $result->getScore()];
	}

	private function getScore(\Szurubooru\Entities\Entity $entity)
	{
		$this->privilegeService->assertLoggedIn();
		$user = $this->authService->getLoggedInUser();
		$result = $this->scoreService->getScore($user, $entity);
		return ['score' => $result ? $result->getScore() : 0];
	}
}
