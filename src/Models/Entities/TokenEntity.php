<?php
class TokenEntity extends AbstractEntity
{
	public $userId;
	public $token;
	public $used;
	public $expires;

	public function getUser()
	{
		return UserModel::findById($this->userId);
	}

	public function setUser($user)
	{
		$this->userId = $user ? $user->id : null;
	}
}
