<?php
class SqlMaxOperator extends SqlUnaryOperator
{
	public function getAsStringNonEmpty()
	{
		return 'MAX (' . $this->subject->getAsString() . ')';
	}
}
