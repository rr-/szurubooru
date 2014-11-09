<?php
namespace Szurubooru\Entities;
use Szurubooru\Entities\User;

final class Snapshot extends Entity
{
	const TYPE_POST = 0;
	const TYPE_TAG = 1;

	const OPERATION_CREATION = 0;
	const OPERATION_CHANGE = 1;
	const OPERATION_DELETE = 2;

	const LAZY_LOADER_USER = 'user';

	private $time;
	private $type;
	private $primaryKey;
	private $operation;
	private $userId;
	private $data;
	private $dataDifference;

	public function getTime()
	{
		return $this->time;
	}

	public function setTime($time)
	{
		$this->time = $time;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setType($type)
	{
		$this->type = $type;
	}

	public function getPrimaryKey()
	{
		return $this->primaryKey;
	}

	public function setPrimaryKey($primaryKey)
	{
		$this->primaryKey = $primaryKey;
	}

	public function getOperation()
	{
		return $this->operation;
	}

	public function setOperation($operation)
	{
		$this->operation = $operation;
	}

	public function getUserId()
	{
		return $this->userId;
	}

	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

	public function getData()
	{
		return $this->data;
	}

	public function setData($data)
	{
		$this->data = $data;
	}

	public function getDataDifference()
	{
		return $this->dataDifference;
	}

	public function setDataDifference($dataDifference)
	{
		$this->dataDifference = $dataDifference;
	}

	public function getUser()
	{
		return $this->lazyLoad(self::LAZY_LOADER_USER, null);
	}

	public function setUser(User $user = null)
	{
		$this->lazySave(self::LAZY_LOADER_USER, $user);
		$this->userId = $user ? $user->getId() : null;
	}
}
