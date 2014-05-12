<?php
class ToggleUserBanJobTest extends AbstractTest
{
	public function testBanning()
	{
		$this->grantAccess('banUser');
		$user = $this->mockUser();
		$this->login($user);

		$this->assert->isFalse($user->isBanned());

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new ToggleUserBanJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_STATE => 1,
				]);
		});

		$this->assert->isTrue($user->isBanned());
	}

	public function testUnbanning()
	{
		$this->grantAccess('banUser');
		$user = $this->mockUser();
		$this->login($user);

		$this->assert->isFalse($user->isBanned());

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new ToggleUserBanJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_STATE => 1,
				]);
		});

		$this->assert->isTrue($user->isBanned());

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new ToggleUserBanJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_STATE => 0,
				]);
		});

		$this->assert->isFalse($user->isBanned());
	}
}
