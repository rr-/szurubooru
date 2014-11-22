<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Search\Filters\SnapshotFilter;
use Szurubooru\Search\Requirements\Requirement;
use Szurubooru\Search\Requirements\RequirementSingleValue;
use Szurubooru\Services\HistoryService;
use Szurubooru\Services\PostSnapshotProvider;

class PostHistoryService
{
	private $transactionManager;
	private $historyService;
	private $postSnapshotProvider;

	public function __construct(
		TransactionManager $transactionManager,
		HistoryService $historyService,
		PostSnapshotProvider $postSnapshotProvider)
	{
		$this->transactionManager = $transactionManager;
		$this->historyService = $historyService;
		$this->postSnapshotProvider = $postSnapshotProvider;
	}

	public function getPostHistory(Post $post)
	{
		$transactionFunc = function() use ($post)
		{
			$filter = new SnapshotFilter();

			$requirement = new Requirement();
			$requirement->setType(SnapshotFilter::REQUIREMENT_PRIMARY_KEY);
			$requirement->setValue(new RequirementSingleValue($post->getId()));
			$filter->addRequirement($requirement);

			$requirement = new Requirement();
			$requirement->setType(SnapshotFilter::REQUIREMENT_TYPE);
			$requirement->setValue(new RequirementSingleValue(Snapshot::TYPE_POST));
			$filter->addRequirement($requirement);

			return $this->historyService->getFiltered($filter)->getEntities();
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function savePostCreation(Post $post)
	{
		$this->historyService->saveSnapshot($this->postSnapshotProvider->getCreationSnapshot($post));
	}

	public function savePostChange(Post $post)
	{
		$this->historyService->saveSnapshot($this->postSnapshotProvider->getChangeSnapshot($post));
	}

	public function savePostDeletion(Post $post)
	{
		$this->historyService->saveSnapshot($this->postSnapshotProvider->getDeleteSnapshot($post));
	}
}
