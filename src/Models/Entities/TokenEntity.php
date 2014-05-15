<?php
final class TokenEntity extends AbstractEntity implements IValidatable
{
	private $userId;
	private $token;
	private $used;
	private $expires;

	public function fillNew()
	{
		$this->used = false;

		$tokenText = '';
		while (true)
		{
			$tokenText =  md5(mt_rand() . uniqid());
			$token = TokenModel::tryGetByToken($tokenText);
			if (!$token)
				break;
		}

		$this->token = $tokenText;
	}

	public function fillFromDatabase($row)
	{
		$this->id = (int) $row['id'];
		$this->userId = TextHelper::toIntegerOrNull($row['user_id']);
		$this->token = $row['token'];
		$this->used = TextHelper::toBooleanOrNull($row['used']);
		$this->expires = $row['expires'];
	}

	public function validate()
	{
		if (empty($this->token))
			throw new Exception('Trying to save empty token');
	}

	public function getText()
	{
		return $this->token;
	}

	public function setText($tokenText)
	{
		$this->token = $tokenText;
	}

	public function isUsed()
	{
		return $this->used;
	}

	public function setUsed($used)
	{
		$this->used = $used;
	}

	public function getExpirationTime()
	{
		return $this->expires;
	}

	public function setExpirationTime($unixTime)
	{
		$this->expires = $unixTime;
	}

	public function getUser()
	{
		return UserModel::getById($this->userId);
	}

	public function getUserId()
	{
		return $this->userId;
	}

	public function setUser($user)
	{
		$this->userId = $user ? $user->getId() : null;
	}
}
