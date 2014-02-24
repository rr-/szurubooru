<?php
class TokenModel extends AbstractCrudModel
implements IModel
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

			$stmt = new SqlUpdateStatement();
			$stmt->setTable('user_token');
			$stmt->setCriterion(new SqlEqualsFunctor('id', new SqlBinding($token->id)));

			foreach ($bindings as $key => $val)
				$stmt->setColumn($key, new SqlBinding($val));

			Database::exec($stmt);

		});
	}



	public static function findByToken($key, $throw = true)
	{
		if (empty($key))
			throw new SimpleNotFoundException('Invalid security token');

		$stmt = new SqlSelectStatement();
		$stmt->setTable('user_token');
		$stmt->setColumn('*');
		$stmt->setCriterion(new SqlEqualsFunctor('token', new SqlBinding($key)));

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
