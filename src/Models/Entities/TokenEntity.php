<?php
class TokenEntity extends AbstractEntity implements IValidatable
{
	public $userId;
	public $token;
	public $used;
	public $expires;

	public function validate()
	{
		//todo
	}

	public function getUser()
	{
		return UserModel::findById($this->userId);
	}

	public function setUser($user)
	{
		$this->userId = $user ? $user->id : null;
	}
}
