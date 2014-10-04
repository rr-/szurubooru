<?php
namespace Szurubooru\Services;

class HistoryService
{
	private $validator;
	private $snapshotDao;
	private $globalParamDao;
	private $timeService;
	private $authService;
	private $transactionManager;

	public function __construct(
		\Szurubooru\Validator $validator,
		\Szurubooru\Dao\SnapshotDao $snapshotDao,
		\Szurubooru\Dao\GlobalParamDao $globalParamDao,
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Services\TimeService $timeService,
		\Szurubooru\Services\AuthService $authService)
	{
		$this->validator = $validator;
		$this->snapshotDao = $snapshotDao;
		$this->globalParamDao = $globalParamDao;
		$this->timeService = $timeService;
		$this->authService = $authService;
		$this->transactionManager = $transactionManager;
	}

	public function getFiltered(\Szurubooru\SearchServices\Filters\SnapshotFilter $filter)
	{
		$transactionFunc = function() use ($filter)
		{
			return $this->snapshotDao->findFiltered($filter);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getPostHistory(\Szurubooru\Entities\Post $post)
	{
		$filter = new \Szurubooru\SearchServices\Filters\SnapshotFilter();

		$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
		$requirement->setType(\Szurubooru\SearchServices\Filters\SnapshotFilter::REQUIREMENT_PRIMARY_KEY);
		$requirement->setValue(new \Szurubooru\SearchServices\Requirements\RequirementSingleValue($post->getId()));
		$filter->addRequirement($requirement);

		$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
		$requirement->setType(\Szurubooru\SearchServices\Filters\SnapshotFilter::REQUIREMENT_TYPE);
		$requirement->setValue(new \Szurubooru\SearchServices\Requirements\RequirementSingleValue(\Szurubooru\Entities\Snapshot::TYPE_POST));
		$filter->addRequirement($requirement);

		return $this->getFiltered($filter)->getEntities();
	}

	public function saveSnapshot(\Szurubooru\Entities\Snapshot $snapshot)
	{
		$transactionFunc = function() use ($snapshot)
		{
			$otherSnapshots = $this->snapshotDao->findByTypeAndKey($snapshot->getType(), $snapshot->getPrimaryKey());
			if ($otherSnapshots)
			{
				$lastSnapshot = array_shift($otherSnapshots);
				if ($lastSnapshot->getData() === $snapshot->getData())
					return $lastSnapshot;

				$dataDifference = $this->getSnapshotDataDifference($snapshot->getData(), $lastSnapshot->getData());
				$snapshot->setDataDifference($dataDifference);
			}
			else
			{
				$dataDifference = $this->getSnapshotDataDifference($snapshot->getData(), []);
				$snapshot->setDataDifference($dataDifference);
			}

			$snapshot->setTime($this->timeService->getCurrentTime());
			$snapshot->setUser($this->authService->getLoggedInUser());
			return $this->snapshotDao->save($snapshot);
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function getPostDeleteSnapshot(\Szurubooru\Entities\Post $post)
	{
		$snapshot = $this->getPostSnapshot($post);
		$snapshot->setData([]);
		$snapshot->setOperation(\Szurubooru\Entities\Snapshot::OPERATION_DELETE);
		return $snapshot;
	}

	public function getPostChangeSnapshot(\Szurubooru\Entities\Post $post)
	{
		$featuredPostParam = $this->globalParamDao->findByKey(\Szurubooru\Entities\GlobalParam::KEY_FEATURED_POST);
		$isFeatured = ($featuredPostParam and intval($featuredPostParam->getValue()) === $post->getId());

		$data =
		[
			'source' => $post->getSource(),
			'safety' => \Szurubooru\Helpers\EnumHelper::postSafetyToString($post->getSafety()),
			'contentChecksum' => $post->getContentChecksum(),
			'featured' => $isFeatured,

			'tags' =>
				array_map(
					function ($tag)
					{
						return $tag->getName();
					},
					$post->getTags()),

			'relations' =>
				array_map(
					function ($post)
					{
						return $post->getId();
					},
					$post->getRelatedPosts()),

		];

		$snapshot = $this->getPostSnapshot($post);
		$snapshot->setOperation(\Szurubooru\Entities\Snapshot::OPERATION_CHANGE);
		$snapshot->setData($data);
		return $snapshot;
	}

	public function getSnapshotDataDifference($newData, $oldData)
	{
		$diffFunction = function($base, $other)
		{
			$result = [];
			foreach ($base as $key => $value)
			{
				if (is_array($base[$key]))
				{
					foreach ($base[$key] as $subValue)
					{
						if (!isset($other[$key]) or !in_array($subValue, $other[$key]))
							$result[] = [$key, $subValue];
					}
				}
				elseif (!isset($other[$key]) or $base[$key] !== $other[$key])
				{
					$result[] = [$key, $value];
				}
			}
			return $result;

		};

		return [
			'+' => $diffFunction($newData, $oldData),
			'-' => $diffFunction($oldData, $newData),
		];
	}

	private function getPostSnapshot(\Szurubooru\Entities\Post $post)
	{
		$snapshot = new \Szurubooru\Entities\Snapshot();
		$snapshot->setType(\Szurubooru\Entities\Snapshot::TYPE_POST);
		$snapshot->setPrimaryKey($post->getId());
		return $snapshot;
	}
}
