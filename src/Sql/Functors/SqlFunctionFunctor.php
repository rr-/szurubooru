<?php
abstract class SqlFunctionFunctor extends SqlFunctor
{
	protected $subjects;

	public function __construct()
	{
		$subjects = func_get_args();
		$expectedArgumentCount = (array) $this->getArgumentCount();
		if (!in_array(count($subjects), $expectedArgumentCount))
			throw new Exception('Unepxected argument count for ' . get_called_class());

		foreach ($subjects as $subject)
			$this->subjects []= $this->attachExpression($subject);
	}

	protected abstract function getFunctionName();
	protected abstract function getArgumentCount();

	public function getAsString()
	{
		return $this->getFunctionName()
			. ' ('
			. join(', ', array_map(function($subject)
				{
					return self::surroundBraces($subject);
				}, $this->subjects))
			. ')';
	}
}
