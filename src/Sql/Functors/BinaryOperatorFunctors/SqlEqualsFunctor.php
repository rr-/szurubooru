<?php
class SqlEqualsFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return '=';
	}
}
