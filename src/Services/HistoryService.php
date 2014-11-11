<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\SnapshotDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\Snapshot;
use Szurubooru\SearchServices\Filters\SnapshotFilter;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\TimeService;

class HistoryService
{
	private $snapshotDao;
	private $timeService;
	private $authService;
	private $transactionManager;

	public function __construct(
		SnapshotDao $snapshotDao,
		TransactionManager $transactionManager,
		TimeService $timeService,
		AuthService $authService)
	{
		$this->snapshotDao = $snapshotDao;
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

	public function saveSnapshot(Snapshot $snapshot)
	{
		$transactionFunc = function() use ($snapshot)
		{
			$otherSnapshots = $this->snapshotDao->findByTypeAndKey($snapshot->getType(), $snapshot->getPrimaryKey());
			if ($otherSnapshots)
			{
				$lastSnapshot = array_shift($otherSnapshots);
				$dataDifference = $this->getSnapshotDataDifference($snapshot->getData(), $lastSnapshot->getData());
				$snapshot->setDataDifference($dataDifference);
				if (empty($dataDifference['+']) && empty($dataDifference['-']))
					return $lastSnapshot;
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
						if (!isset($other[$key]) || !in_array($subValue, $other[$key]))
							$result[] = [$key, $subValue];
					}
				}
				elseif (!isset($other[$key]) || $base[$key] !== $other[$key])
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
}
