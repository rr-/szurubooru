<?php
namespace Szurubooru\Routes\Scores;
use Szurubooru\Entities\Entity;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\CommentService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\ScoreService;

class SetCommentScore extends AbstractScoreRoute
{
	private $commentService;

	public function __construct(
		PrivilegeService $privilegeService,
		AuthService $authService,
		CommentService $commentService,
		ScoreService $scoreService,
		InputReader $inputReader)
	{
		parent::__construct(
			$authService,
			$inputReader,
			$privilegeService,
			$scoreService);

		$this->commentService = $commentService;
	}

	public function getMethods()
	{
		return ['POST', 'PUT'];
	}

	public function getUrl()
	{
		return '/api/comments/:commentId/score';
	}

	public function work($args)
	{
		$comment = $this->commentService->getById($args['commentId']);
		return $this->setScore($comment);
	}
}
