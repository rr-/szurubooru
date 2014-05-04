<?php
abstract class AbstractUserJob extends AbstractJob
{
	protected $user;

	public function prepare()
	{
		$userName = $this->getArgument(self::USER_NAME);
		$this->user = UserModel::findByNameOrEmail($userName);
	}
}
