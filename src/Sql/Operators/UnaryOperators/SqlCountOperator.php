<?php
class SqlCountOperator extends SqlUnaryOperator
{
	public function getAsStringNonEmpty()
	{
		return 'COUNT (' . $this->subject->getAsString() . ')';
	}
}
