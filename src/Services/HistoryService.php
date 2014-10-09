<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\GlobalParamDao;
use Szurubooru\Dao\SnapshotDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\GlobalParam;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\SearchServices\Filters\SnapshotFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\SearchServices\Requirements\RequirementSingleValue;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\TimeService;
use Szurubooru\Validator;

class HistoryService
{
	private $validator;
	private $snapshotDao;
	private $globalParamDao;
	private $timeService;
	private $authService;
	private $transactionManager;

	public function __construct(
		Validator $validator,
		SnapshotDao $snapshotDao,
		GlobalParamDao $globalParamDao,
		TransactionManager $transactionManager,
		TimeService $timeService,
		AuthService $authService)
	{
		$this->validator = $validator;
		$this->snapshotDao = $snapshotDao;
		$this->globalParamDao = $globalParamDao;
		$this->timeService = $timeService;
		$this->authService = $authService;
		$this->transactionManager = $transactionManager;
	}

	public function getFiltered(SnapshotFilter $filter)
	{
		$transactionFunc = function() use ($filter)
		{
			return $this->snapshotDao->findFiltered($filter);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getPostHistory(Post $post)
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

		return $this->getFiltered($filter)->getEntities();
	}

	public function saveSnapshot(Snapshot $snapshot)
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

	public function getPostDeleteSnapshot(Post $post)
	{
		$snapshot = $this->getPostSnapshot($post);
		$snapshot->setData([]);
		$snapshot->setOperation(Snapshot::OPERATION_DELETE);
		return $snapshot;
	}

	public function getPostChangeSnapshot(Post $post)
	{
		$featuredPostParam = $this->globalParamDao->findByKey(GlobalParam::KEY_FEATURED_POST);
		$isFeatured = ($featuredPostParam and intval($featuredPostParam->getValue()) === $post->getId());

		$flags = [];
		if ($post->getFlags() & Post::FLAG_LOOP)
			$flags []= 'loop';

		$data =
		[
			'source' => $post->getSource(),
			'safety' => EnumHelper::postSafetyToString($post->getSafety()),
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

			'flags' => $flags,
		];

		$snapshot = $this->getPostSnapshot($post);
		$snapshot->setOperation(Snapshot::OPERATION_CHANGE);
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

	private function getPostSnapshot(Post $post)
	{
		$snapshot = new Snapshot();
		$snapshot->setType(Snapshot::TYPE_POST);
		$snapshot->setPrimaryKey($post->getId());
		return $snapshot;
	}
}
