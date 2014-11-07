<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\Tag;
use Szurubooru\Entities\Snapshot;
use Szurubooru\SearchServices\Filters\SnapshotFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\SearchServices\Requirements\RequirementSingleValue;
use Szurubooru\Services\HistoryService;
use Szurubooru\Services\TagSnapshotProvider;

class TagHistoryService
{
	private $transactionManager;
	private $historyService;
	private $tagSnapshotProvider;

	public function __construct(
		TransactionManager $transactionManager,
		HistoryService $historyService,
		TagSnapshotProvider $tagSnapshotProvider)
	{
		$this->transactionManager = $transactionManager;
		$this->historyService = $historyService;
		$this->tagSnapshotProvider = $tagSnapshotProvider;
	}

	public function getTagHistory(Tag $tag)
	{
		$transactionFunc = function() use ($tag)
		{
			$filter = new SnapshotFilter();

			$requirement = new Requirement();
			$requirement->setType(SnapshotFilter::REQUIREMENT_PRIMARY_KEY);
			$requirement->setValue(new RequirementSingleValue($tag->getId()));
			$filter->addRequirement($requirement);

			$requirement = new Requirement();
			$requirement->setType(SnapshotFilter::REQUIREMENT_TYPE);
			$requirement->setValue(new RequirementSingleValue(Snapshot::TYPE_TAG));
			$filter->addRequirement($requirement);

			return $this->historyService->getFiltered($filter)->getEntities();
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function saveTagChange(Tag $tag)
	{
		$this->historyService->saveSnapshot($this->tagSnapshotProvider->getTagChangeSnapshot($tag));
	}

	public function saveTagDeletion(Tag $tag)
	{
		$this->historyService->saveSnapshot($this->tagSnapshotProvider->getTagDeleteSnapshot($tag));
	}
}

