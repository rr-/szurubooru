<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Snapshot;
use Szurubooru\SearchServices\Filters\SnapshotFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\SearchServices\Requirements\RequirementSingleValue;
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

	public function savePostChange(Post $post)
	{
		$this->historyService->saveSnapshot($this->postSnapshotProvider->getPostChangeSnapshot($post));
	}

	public function savePostDeletion(Post $post)
	{
		$this->historyService->saveSnapshot($this->postSnapshotProvider->getPostDeleteSnapshot($post));
	}
}
