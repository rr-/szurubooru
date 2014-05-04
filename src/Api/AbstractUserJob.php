<?php
abstract class AbstractUserJob extends AbstractJob
{
	protected $user;

	public function prepare()
	{
		if ($this->hasArgument(self::USER_ENTITY))
		{
			$this->user = $this->getArgument(self::USER_ENTITY);
		}
		else
		{
			$userName = $this->getArgument(self::USER_NAME);
			$this->user = UserModel::findByNameOrEmail($userName);
		}
	}
}
