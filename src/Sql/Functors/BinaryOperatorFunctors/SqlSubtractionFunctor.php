<?php
class SqlSubtractionFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return '-';
	}
}
