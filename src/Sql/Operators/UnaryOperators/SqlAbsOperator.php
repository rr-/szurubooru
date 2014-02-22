<?php
class SqlAbsOperator extends SqlUnaryOperator
{
	public function getAsStringNonEmpty()
	{
		return 'ABS (' . $this->subject->getAsString() . ')';
	}
}
