<?php
class SqlEqualsOrLesserFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return '<=';
	}
}
