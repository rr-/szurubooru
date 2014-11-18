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
			$snapshot->setTime($this->timeService->getCurrentTime());
			$snapshot->setUser($this->authService->getLoggedInUser());

			$lastSnapshot = $this->getLastSnapshot($snapshot);

			$dataDifference = $this->getSnapshotDataDifference($snapshot, $lastSnapshot);
			$snapshot->setDataDifference($dataDifference);

			if ($snapshot->getOperation() !== Snapshot::OPERATION_DELETE && $lastSnapshot !== null)
			{
				//don't save if nothing changed
				if (empty($dataDifference['+']) && empty($dataDifference['-']))
				{
					if ($snapshot->getId())
						$this->snapshotDao->deleteById($snapshot->getId());
					return $lastSnapshot;
				}

				//merge recent edits
				$isFresh = ((strtotime($snapshot->getTime()) - strtotime($lastSnapshot->getTime())) <= 5 * 60);
				if ($isFresh && $lastSnapshot->getUserId() === $snapshot->getUserId())
				{
					$lastSnapshot->setData($snapshot->getData());
					return $this->saveSnapshot($lastSnapshot);
				}
			}

			return $this->snapshotDao->save($snapshot);
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function getSnapshotDataDifference(Snapshot $newSnapshot, Snapshot $oldSnapshot = null)
	{
		return $this->getDataDifference(
			$newSnapshot->getData(),
			$oldSnapshot ? $oldSnapshot->getData() : []);
	}

	public function getDataDifference($newData, $oldData)
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

	private function getLastSnapshot(Snapshot $reference)
	{
		$earlierSnapshots = $this->snapshotDao->findEarlierSnapshots($reference);
		return empty($earlierSnapshots) ? null : array_shift($earlierSnapshots);
	}
}
