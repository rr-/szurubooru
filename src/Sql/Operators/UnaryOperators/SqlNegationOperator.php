<?php
class SqlNegationOperator extends SqlUnaryOperator
{
	public function getAsStringNonEmpty()
	{
		return 'NOT (' . $this->subject->getAsString() . ')';
	}
}
