<?php
class SqlExistsOperator extends SqlUnaryOperator
{
	public function getAsStringNonEmpty()
	{
		return 'EXISTS (' . $this->subject->getAsString() . ')';
	}
}
