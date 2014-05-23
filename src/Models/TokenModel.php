<?php
use \Chibi\Sql as Sql;

final class TokenModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'user_token';
	}

	protected static function saveSingle($token)
	{
		$token->validate();

		Core::getDatabase()->transaction(function() use ($token)
		{
			self::forgeId($token);

			$bindings = [
				'user_id' => $token->getUserId(),
				'token' => $token->getText(),
				'used' => $token->isUsed() ? 1 : 0,
				'expires' => $token->getExpirationTime(),
				];

			$stmt = Sql\Statements::update();
			$stmt->setTable('user_token');
			$stmt->setCriterion(Sql\Functors::equals('id', new Sql\Binding($token->getId())));

			foreach ($bindings as $key => $val)
				$stmt->setColumn($key, new Sql\Binding($val));

			Core::getDatabase()->execute($stmt);
		});

		return $token;
	}

	public static function getByToken($key)
	{
		$ret = self::tryGetByToken($key);
		if (!$ret)
			throw new SimpleNotFoundException('No user with such security token');
		return $ret;
	}

	public static function tryGetByToken($key)
	{
		if (empty($key))
			throw new SimpleNotFoundException('Invalid security token');

		$stmt = Sql\Statements::select();
		$stmt->setTable('user_token');
		$stmt->setColumn('*');
		$stmt->setCriterion(Sql\Functors::equals('token', new Sql\Binding($key)));

		$row = Core::getDatabase()->fetchOne($stmt);
		return $row
			? self::spawnFromDatabaseRow($row)
			: null;
	}

	public static function checkValidity($token)
	{
		if (empty($token))
			throw new SimpleException('Invalid security token');

		if ($token->isUsed())
			throw new SimpleException('This token was already used');

		if ($token->getExpirationTime() !== null and time() > $token->getExpirationTime())
			throw new SimpleException('This token has expired');
	}
}
