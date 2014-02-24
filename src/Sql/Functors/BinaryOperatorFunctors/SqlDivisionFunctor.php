<?php
class SqlAdditionFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return '/';
	}
}
