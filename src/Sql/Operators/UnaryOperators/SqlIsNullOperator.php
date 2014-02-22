<?php
class SqlIsNullOperator extends SqlUnaryOperator
{
	public function getAsStringNonEmpty()
	{
		return '(' . $this->subject->getAsString() . ') IS NULL';
	}
}
