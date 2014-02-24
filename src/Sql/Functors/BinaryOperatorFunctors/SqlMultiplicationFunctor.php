<?php
class SqlMultiplicationFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return '*';
	}
}
