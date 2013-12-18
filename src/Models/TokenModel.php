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

			$query = (new SqlQuery)
				->update('user_token')
				->set(join(', ', array_map(function($key) { return $key . ' = ?'; }, array_keys($bindings))))
				->put(array_values($bindings))
				->where('id = ?')->put($token->id);
			Database::query($query);

		});
	}



	public static function findByToken($key, $throw = true)
	{
		if (empty($key))
			throw new SimpleException('Invalid security token');

		$query = (new SqlQuery)
			->select('*')
			->from('user_token')
			->where('token = ?')->put($key);

		$row = Database::fetchOne($query);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleException('No user with such security token');
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
