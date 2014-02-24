<?php
class SqlLesserFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return '<';
	}
}
