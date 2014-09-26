<?php
namespace Szurubooru\Controllers\ViewProxies;

class SnapshotViewProxy extends AbstractViewProxy
{
	private $userViewProxy;

	public function __construct(UserViewProxy $userViewProxy)
	{
		$this->userViewProxy = $userViewProxy;
	}

	public function fromEntity($snapshot, $config = [])
	{
		$result = new \StdClass;
		if ($snapshot)
		{
			$result->time = $snapshot->getTime();
			$result->type = $snapshot->getType();
			$result->primaryKey = $snapshot->getPrimaryKey();
			$result->operation = $snapshot->getOperation();
			$result->user = $this->userViewProxy->fromEntity($snapshot->getUser());
			$result->data = $snapshot->getData();
			$result->dataDifference = $snapshot->getDataDifference();
		}
		return $result;
	}
}

