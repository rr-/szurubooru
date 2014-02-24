<?php
class SqlNullFunctor extends SqlFunctor
{
	public function getAsString()
	{
		return 'NULL';
	}
}
