<?php
class SqlLikeFunctor extends SqlBinaryFunctor
{
	protected function getOperator()
	{
		return 'LIKE';
	}
}
