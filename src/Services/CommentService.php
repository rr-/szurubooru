<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\CommentDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\Comment;
use Szurubooru\Entities\Post;
use Szurubooru\Search\Filters\CommentFilter;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\TimeService;
use Szurubooru\Validator;

class CommentService
{
    private $validator;
    private $commentDao;
    private $transactionManager;
    private $authService;
    private $timeService;

    public function __construct(
        Validator $validator,
        CommentDao $commentDao,
        TransactionManager $transactionManager,
        AuthService $authService,
        TimeService $timeService)
    {
        $this->validator = $validator;
        $this->commentDao = $commentDao;
        $this->transactionManager = $transactionManager;
        $this->authService = $authService;
        $this->timeService = $timeService;
    }

    public function getById($commentId)
    {
        $transactionFunc = function() use ($commentId)
        {
            $comment = $this->commentDao->findById($commentId);
            if (!$comment)
                throw new \InvalidArgumentException('Comment with ID "' . $commentId . '" was not found.');
            return $comment;
        };
        return $this->transactionManager->rollback($transactionFunc);
    }

    public function getByPost(Post $post)
    {
        $transactionFunc = function() use ($post)
        {
            return $this->commentDao->findByPost($post);
        };
        return $this->transactionManager->rollback($transactionFunc);
    }

    public function getFiltered(CommentFilter $filter)
    {
        $transactionFunc = function() use ($filter)
        {
            return $this->commentDao->findFiltered($filter);
        };
        return $this->transactionManager->rollback($transactionFunc);
    }

    public function createComment(Post $post, $text)
    {
        $transactionFunc = function() use ($post, $text)
        {
            $comment = new Comment();
            $comment->setCreationTime($this->timeService->getCurrentTime());
            $comment->setLastEditTime($this->timeService->getCurrentTime());
            $comment->setUser($this->authService->isLoggedIn() ? $this->authService->getLoggedInUser() : null);
            $comment->setPost($post);

            $this->updateCommentText($comment, $text);

            return $this->commentDao->save($comment);
        };
        return $this->transactionManager->commit($transactionFunc);
    }

    public function updateComment(Comment $comment, $newText)
    {
        $transactionFunc = function() use ($comment, $newText)
        {
            $comment->setLastEditTime($this->timeService->getCurrentTime());

            $this->updateCommentText($comment, $newText);
            return $this->commentDao->save($comment);
        };
        return $this->transactionManager->commit($transactionFunc);
    }

    public function deleteComment(Comment $comment)
    {
        $transactionFunc = function() use ($comment)
        {
            $this->commentDao->deleteById($comment->getId());
        };
        $this->transactionManager->commit($transactionFunc);
    }

    private function updateCommentText(Comment $comment, $text)
    {
        $this->validator->validateLength($text, 5, 5000, 'Comment text');
        $comment->setText($text);
    }
}
