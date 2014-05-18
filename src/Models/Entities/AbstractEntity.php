<?php
abstract class AbstractEntity implements IValidatable
{
	protected $model;
	protected $id;
	protected $__cache = [];

	public abstract function fillNew();
	public abstract function fillFromDatabase($row);

	public function __construct($model)
	{
		$this->model = $model;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getCache($key)
	{
		return isset($this->__cache[$key])
			? $this->__cache[$key]
			: null;
	}

	public function setCache($key, $value)
	{
		$this->__cache[$key] = $value;
	}

	public function removeCache($key)
	{
		unset($this->__cache[$key]);
	}

	public function resetCache()
	{
		$this->__cache = [];
	}

	public function hasCache($key)
	{
		return isset($this->__cache[$key]);
	}

	protected function getColumnWithCache($columnName)
	{
		if ($this->hasCache($columnName))
			return $this->getCache($columnName);

		$stmt = new \Chibi\Sql\SelectStatement();
		$stmt->setTable($this->model->getTableName());
		$stmt->setColumn($columnName);
		$stmt->setCriterion(new \Chibi\Sql\EqualsFunctor('id', new \Chibi\Sql\Binding($this->getId())));
		$value = \Chibi\Database::fetchOne($stmt)[$columnName];
		$this->setCache($columnName, $value);
		return $value;
	}
}
