<?php
namespace Szurubooru\Controllers;
use Szurubooru\Entities\Entity;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Router;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\CommentService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\ScoreService;

final class ScoreController extends AbstractController
{
	private $privilegeService;
	private $authService;
	private $postService;
	private $commentService;
	private $scoreService;
	private $inputReader;

	public function __construct(
		PrivilegeService $privilegeService,
		AuthService $authService,
		PostService $postService,
		CommentService $commentService,
		ScoreService $scoreService,
		InputReader $inputReader)
	{
		$this->privilegeService = $privilegeService;
		$this->authService = $authService;
		$this->postService = $postService;
		$this->commentService = $commentService;
		$this->scoreService = $scoreService;
		$this->inputReader = $inputReader;
	}

	public function registerRoutes(Router $router)
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

	private function setScore(Entity $entity)
	{
		$this->privilegeService->assertLoggedIn();
		$score = intval($this->inputReader->score);
		$user = $this->authService->getLoggedInUser();
		$result = $this->scoreService->setUserScore($user, $entity, $score);
		return [
			'score' => $this->scoreService->getScoreValue($entity),
			'ownScore' => $result->getScore(),
		];
	}

	private function getScore(Entity $entity)
	{
		$user = $this->authService->getLoggedInUser();
		return [
			'score' => $this->scoreService->getScoreValue($entity),
			'ownScore' => $this->scoreService->getUserScoreValue($user, $entity),
		];
	}
}
