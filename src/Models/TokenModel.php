<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class TokenModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'user_token';
	}

	public static function save($token)
	{
		Database::transaction(function() use ($token)
		{
			self::forgeId($token);

			$bindings = [
				'user_id' => $token->userId,
				'token' => $token->token,
				'used' => $token->used,
				'expires' => $token->expires,
				];

			$stmt = new Sql\UpdateStatement();
			$stmt->setTable('user_token');
			$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($token->id)));

			foreach ($bindings as $key => $val)
				$stmt->setColumn($key, new Sql\Binding($val));

			Database::exec($stmt);
		});
	}

	public static function findByToken($key, $throw = true)
	{
		if (empty($key))
			throw new SimpleNotFoundException('Invalid security token');

		$stmt = new Sql\SelectStatement();
		$stmt->setTable('user_token');
		$stmt->setColumn('*');
		$stmt->setCriterion(new Sql\EqualsFunctor('token', new Sql\Binding($key)));

		$row = Database::fetchOne($stmt);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('No user with such security token');
		return null;
	}

	public static function checkValidity($token)
	{
		if (empty($token))
			throw new SimpleException('Invalid security token');

		if ($token->used)
			throw new SimpleException('This token was already used');

		if ($token->expires !== null and time() > $token->expires)
			throw new SimpleException('This token has expired');
	}

	public static function forgeUnusedToken()
	{
		$tokenText = '';
		while (true)
		{
			$tokenText =  md5(mt_rand() . uniqid());
			$token = self::findByToken($tokenText, false);
			if (!$token)
				return $tokenText;
		}
	}
}
