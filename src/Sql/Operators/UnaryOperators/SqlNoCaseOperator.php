<?php
class SqlNoCaseOperator extends SqlUnaryOperator
{
	public function getAsStringNonEmpty()
	{
		return $this->subject->getAsString() . ' COLLATE NOCASE';
	}
}
