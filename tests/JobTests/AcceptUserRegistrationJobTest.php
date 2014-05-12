<?php
class AcceptUserRegistrationJobTest extends AbstractTest
{
	public function testConfirming()
	{
		$this->grantAccess('acceptUserRegistration');

		$user = $this->mockUser();
		$this->assert->isFalse($user->isStaffConfirmed());

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new AcceptUserRegistrationJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$this->assert->isTrue($user->isStaffConfirmed());
	}
}
