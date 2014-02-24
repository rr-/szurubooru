<?php
class SqlGreaterFunctor extends SqlBinaryOperatorFunctor
{
	protected function getOperator()
	{
		return '>';
	}
}
