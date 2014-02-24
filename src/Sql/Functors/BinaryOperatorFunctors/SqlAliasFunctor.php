<?php
class SqlAliasFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return 'AS';
	}

	public function getAsString()
	{
		return self::surroundBraces($this->subjects[0])
			. ' ' . $this->getOperator()
			. ' ' . $this->subjects[1]->getAsString();
	}
}
