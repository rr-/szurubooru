<?php
use \Chibi\Sql as Sql;

abstract class AbstractSearchParser
{
	protected $statement;

	public function decorate(Sql\SelectStatement $statement, $filterString)
	{
		$this->statement = $statement;

		$tokens = preg_split('/\s+/', $filterString);
		$tokens = array_filter($tokens);
		$tokens = array_unique($tokens);
		$this->processSetup($tokens);

		foreach ($tokens as $token)
		{
			$neg = false;
			if ($token{0} == '-')
			{
				$token = substr($token, 1);
				$neg = true;
			}

			if (strpos($token, ':') !== false)
			{
				list ($key, $value) = explode(':', $token, 2);
				$key = strtolower($key);

				if ($key == 'order')
				{
					$this->internalProcessOrderToken($value, $neg);
				}
				else
				{
					if (!$this->processComplexToken($key, $value, $neg))
						throw new SimpleException('Invalid search token: ' . $key);
				}
			}
			else
			{
				if (!$this->processSimpleToken($token, $neg))
					throw new SimpleException('Invalid search token: ' . $token);
			}
		}
		$this->processTeardown();
	}

	protected function processSetup(&$tokens)
	{
	}

	protected function processTeardown()
	{
	}

	protected function internalProcessOrderToken($orderToken, $neg)
	{
		$arr = preg_split('/[;,]/', $orderToken);
		if (count($arr) == 1)
			$arr []= 'desc';

		if (count($arr) != 2)
			throw new SimpleException('Invalid search order token: ' . $orderToken);

		$orderByString = strtolower(array_shift($arr));
		$orderDirString = strtolower(array_shift($arr));
		if ($orderDirString == 'asc')
			$orderDir = Sql\SelectStatement::ORDER_ASC;
		elseif ($orderDirString == 'desc')
			$orderDir = Sql\SelectStatement::ORDER_DESC;
		else
			throw new SimpleException('Invalid search order direction: ' . $searchOrderDir);

		if ($neg)
		{
			$orderDir = $orderDir == Sql\SelectStatement::ORDER_ASC
				? Sql\SelectStatement::ORDER_DESC
				: Sql\SelectStatement::ORDER_ASC;
		}

		if (!$this->processOrderToken($orderByString, $orderDir))
			throw new SimpleException('Invalid search order type: ' . $orderByString);
	}

	protected function processComplexToken($key, $value, $neg)
	{
		return false;
	}

	protected function processSimpleToken($value, $neg)
	{
		return false;
	}

	protected function processOrderToken($orderToken, $orderDir)
	{
		return false;
	}
}
