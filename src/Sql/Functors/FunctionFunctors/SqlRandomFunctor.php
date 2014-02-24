<?php
class SqlRandomFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		$config = \Chibi\Registry::getConfig();
		return $config->main->dbDriver == 'sqlite'
			? 'RANDOM'
			: 'RAND';
	}

	public function getArgumentCount()
	{
		return 2;
	}
}
