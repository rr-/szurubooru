<?php
class Model_Token extends AbstractModel
{
	public static function locate($key, $throw = true)
	{
		if (empty($key))
			throw new SimpleException('Invalid security token');

		$token = R::findOne('usertoken', 'token = ?', [$key]);
		if ($token === null)
		{
			if ($throw)
				throw new SimpleException('No user with security token');
			return null;
		}

		if ($token->used)
			throw new SimpleException('This token was already used');

		if ($token->expires !== null and time() > $token->expires)
			throw new SimpleException('This token has expired');

		return $token;
	}
}
