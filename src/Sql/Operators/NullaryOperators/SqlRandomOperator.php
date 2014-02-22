<?php
class SqlRandomOperator extends SqlNullaryOperator
{
	public function getAsString()
	{
		$config = \Chibi\Registry::getConfig();
		return $config->main->dbDriver == 'sqlite'
			? 'RANDOM()'
			: 'RAND()';
	}
}
