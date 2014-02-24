<?php
class SqlLikeFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return 'LIKE';
	}
}
