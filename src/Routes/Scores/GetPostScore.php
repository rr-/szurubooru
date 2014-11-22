<?php
namespace Szurubooru\Routes\Scores;
use Szurubooru\Entities\Entity;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\ScoreService;

class GetPostScore extends AbstractScoreRoute
{
	private $postService;

	public function __construct(
		PrivilegeService $privilegeService,
		AuthService $authService,
		PostService $postService,
		ScoreService $scoreService,
		InputReader $inputReader)
	{
		parent::__construct(
			$authService,
			$inputReader,
			$privilegeService,
			$scoreService);

		$this->postService = $postService;
	}

	public function getMethods()
	{
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/posts/:postNameOrId/score';
	}

	public function work($args)
	{
		$post = $this->postService->getByNameOrId($args['postNameOrId']);
		return $this->getScore($post);
	}
}
