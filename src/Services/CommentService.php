<?php
namespace Szurubooru\Services;

class CommentService
{
	private $validator;
	private $commentDao;
	private $transactionManager;
	private $authService;
	private $timeService;

	public function __construct(
		\Szurubooru\Validator $validator,
		\Szurubooru\Dao\CommentDao $commentDao,
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\TimeService $timeService)
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

	public function getByPost(\Szurubooru\Entities\Post $post)
	{
		$transactionFunc = function() use ($post)
		{
			return $this->commentDao->findByPost($post);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getFiltered(\Szurubooru\SearchServices\Filters\CommentFilter $filter)
	{
		$transactionFunc = function() use ($filter)
		{
			return $this->commentDao->findFiltered($filter);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function createComment(\Szurubooru\Entities\Post $post, $text)
	{
		$transactionFunc = function() use ($post, $text)
		{
			$comment = new \Szurubooru\Entities\Comment();
			$comment->setCreationTime($this->timeService->getCurrentTime());
			$comment->setLastEditTime($this->timeService->getCurrentTime());
			$comment->setUser($this->authService->isLoggedIn() ? $this->authService->getLoggedInUser() : null);
			$comment->setPost($post);

			$this->updateCommentText($comment, $text);

			return $this->commentDao->save($comment);
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function updateComment(\Szurubooru\Entities\Comment $comment, $newText)
	{
		$transactionFunc = function() use ($comment, $newText)
		{
			$comment->setLastEditTime($this->timeService->getCurrentTime());

			$this->updateCommentText($comment, $newText);
			return $this->commentDao->save($comment);
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function deleteComment(\Szurubooru\Entities\Comment $comment)
	{
		$transactionFunc = function() use ($comment)
		{
			$this->commentDao->deleteById($comment->getId());
		};
		$this->transactionManager->commit($transactionFunc);
	}

	private function updateCommentText(\Szurubooru\Entities\Comment $comment, $text)
	{
		$this->validator->validateLength($text, 5, 2000, 'Comment text');
		$comment->setText($text);
	}
}
