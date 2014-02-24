<?php
class SqlIsFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return 'IS';
	}
}
