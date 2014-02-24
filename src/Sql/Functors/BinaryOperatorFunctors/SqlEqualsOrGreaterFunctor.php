<?php
class SqlEqualsOrGreaterFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return '>=';
	}
}
