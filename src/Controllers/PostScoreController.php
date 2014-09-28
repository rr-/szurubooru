<?php
namespace Szurubooru\Controllers;

class PostScoreController extends AbstractController
{
	private $privilegeService;
	private $authService;
	private $postService;
	private $postScoreService;
	private $inputReader;

	public function __construct(
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\PostService $postService,
		\Szurubooru\Services\PostScoreService $postScoreService,
		\Szurubooru\Helpers\InputReader $inputReader)
	{
		$this->privilegeService = $privilegeService;
		$this->authService = $authService;
		$this->postService = $postService;
		$this->postScoreService = $postScoreService;
		$this->inputReader = $inputReader;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->get('/api/posts/:postNameOrId/score', [$this, 'getScore']);
		$router->post('/api/posts/:postNameOrId/score', [$this, 'setScore']);
	}

	public function getScore($postNameOrId)
	{
		$this->privilegeService->assertLoggedIn();
		$user = $this->authService->getLoggedInUser();
		$post = $this->postService->getByNameOrId($postNameOrId);
		$result = $this->postScoreService->getScore($user, $post);
		return ['score' => $result ? $result->getScore() : 0];
	}

	public function setScore($postNameOrId)
	{
		$this->privilegeService->assertLoggedIn();
		$score = intval($this->inputReader->score);
		$user = $this->authService->getLoggedInUser();
		$post = $this->postService->getByNameOrId($postNameOrId);
		$result = $this->postScoreService->setScore($user, $post, $score);
		return ['score' => $result->getScore()];
	}
}
