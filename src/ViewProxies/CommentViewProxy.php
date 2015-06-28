<?php
namespace Szurubooru\ViewProxies;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\ScoreService;

class CommentViewProxy extends AbstractViewProxy
{
    private $authService;
    private $scoreService;
    private $userViewProxy;

    const FETCH_OWN_SCORE = 'fetchOwnScore';

    public function __construct(
        AuthService $authService,
        ScoreService $scoreService,
        UserViewProxy $userViewProxy)
    {
        $this->authService = $authService;
        $this->scoreService = $scoreService;
        $this->userViewProxy = $userViewProxy;
    }

    public function fromEntity($comment, $config = [])
    {
        $result = new \StdClass;
        if ($comment)
        {
            $result->id = $comment->getId();
            $result->creationTime = $comment->getCreationTime();
            $result->lastEditTime = $comment->getLastEditTime();
            $result->text = $comment->getText();
            $result->postId = $comment->getPostId();
            $result->user = $this->userViewProxy->fromEntity($comment->getUser());
            $result->score = $comment->getScore();

            if (!empty($config[self::FETCH_OWN_SCORE]) && $this->authService->isLoggedIn())
                $result->ownScore = $this->scoreService->getUserScoreValue($this->authService->getLoggedInUser(), $comment);
        }
        return $result;
    }
}
