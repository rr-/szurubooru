<?php
abstract class SqlVariableFunctor extends SqlFunctor
{
	protected $subjects;

	public function __construct()
	{
		$this->subjects = [];
	}

	public function add($subject)
	{
		$this->subjects []= $this->attachExpression($subject);
		return $this;
	}

	public abstract function getAsStringNonEmpty();
	public abstract function getAsStringEmpty();

	public function getAsString()
	{
		if (empty(array_filter($this->subjects, function($x) { return !empty($x->getAsString()); })))
			return $this->getAsStringEmpty();

		return $this->getAsStringNonEmpty();
	}

	//variable arguments
	public static function fromArray()
	{
		$args = func_get_args();
		$subjects = array_pop($args);
		$instance = (new ReflectionClass(get_called_class()))->newInstanceArgs($args);
		foreach ($subjects as $subject)
			$instance->add($subject);
		return $instance;
	}
}
