<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Dao\PostNoteDao;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\PostNote;
use Szurubooru\FormData\PostNoteFormData;
use Szurubooru\Services\PostHistoryService;
use Szurubooru\Validator;

class PostNotesService
{
	private $validator;
	private $transactionManager;
	private $postNoteDao;
	private $postHistoryService;

	public function __construct(
		Validator $validator,
		TransactionManager $transactionManager,
		PostNoteDao $postNoteDao,
		PostHistoryService $postHistoryService)
	{
		$this->validator = $validator;
		$this->transactionManager = $transactionManager;
		$this->postNoteDao = $postNoteDao;
		$this->postHistoryService = $postHistoryService;
	}

	public function getById($postNoteId)
	{
		$transactionFunc = function() use ($postNoteId)
		{
			$postNote = $this->postNoteDao->findById($postNoteId);
			if (!$postNote)
				throw new \InvalidArgumentException('Post note with ID "' . $postNoteId . '" was not found.');
			return $postNote;
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getByPost(Post $post)
	{
		$transactionFunc = function() use ($post)
		{
			return $this->postNoteDao->findByPostId($post->getId());
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function createPostNote(Post $post, PostNoteFormData $formData)
	{
		$transactionFunc = function() use ($post, $formData)
		{
			$postNote = new PostNote();
			$postNote->setPostId($post->getId());

			$this->updatePostNoteWithFormData($postNote, $formData);
			$this->postNoteDao->save($postNote);

			$this->postHistoryService->savePostChange($post);
			return $postNote;
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function updatePostNote(PostNote $postNote, PostNoteFormData $formData)
	{
		$transactionFunc = function() use ($postNote, $formData)
		{
			$this->updatePostNoteWithFormData($postNote, $formData);
			$this->postNoteDao->save($postNote);

			$this->postHistoryService->savePostChange($postNote->getPost());
			return $postNote;
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function deletePostNote(PostNote $postNote)
	{
		$transactionFunc = function() use ($postNote)
		{
			$this->postNoteDao->deleteById($postNote->getId());
			$this->postHistoryService->savePostChange($postNote->getPost());
		};
		$this->transactionManager->commit($transactionFunc);
	}

	private function updatePostNoteWithFormData(PostNote $postNote, PostNoteFormData $formData)
	{
		$formData->validate($this->validator);
		$postNote->setLeft($formData->left);
		$postNote->setTop($formData->top);
		$postNote->setWidth($formData->width);
		$postNote->setHeight($formData->height);
		$postNote->setText($formData->text);
	}
}
