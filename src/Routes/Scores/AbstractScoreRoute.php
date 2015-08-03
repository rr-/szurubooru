<?php
namespace Szurubooru\Routes\Scores;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Entities\Entity;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\ScoreService;
use Szurubooru\Routes\AbstractRoute;

abstract class AbstractScoreRoute extends AbstractRoute
{
    private $privilegeService;
    private $scoreService;
    private $authService;

    public function __construct(
        AuthService $authService,
        InputReader $inputReader,
        PrivilegeService $privilegeService,
        ScoreService $scoreService)
    {
        $this->authService = $authService;
        $this->inputReader = $inputReader;
        $this->privilegeService = $privilegeService;
        $this->scoreService = $scoreService;
    }

    protected function getScore(Entity $entity)
    {
        $user = $this->authService->getLoggedInUser();
        return [
            'score' => $this->scoreService->getScoreValue($entity),
            'ownScore' => $this->scoreService->getUserScoreValue($user, $entity),
        ];
    }

    protected function setScore(Entity $entity)
    {
        $this->privilegeService->assertLoggedIn();
        $score = intval($this->inputReader->score);
        $user = $this->authService->getLoggedInUser();
        $result = $this->scoreService->setUserScore($user, $entity, $score);
        return [
            'score' => $this->scoreService->getScoreValue($entity),
            'ownScore' => $result->getScore()];
    }
}
