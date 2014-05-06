<?php
class EditUserJobTest extends AbstractTest
{
	public function testSaving()
	{
		$this->grantAccess('changeUserName.own');
		$this->grantAccess('changeUserPassword.own');
		$user = $this->mockUser();

		$newName = 'dummy' . uniqid();

		$user = $this->assert->doesNotThrow(function() use ($user, $newName)
		{
			return Api::run(
				new EditUserJob(),
				[
					EditUserJob::USER_NAME => $user->getName(),
					EditUserNameJob::NEW_USER_NAME => $newName,
					EditUserPasswordJob::NEW_PASSWORD => 'changed',
				]);
		});

		//first user = admin
		$this->assert->areEqual($newName, $user->getName());
		$this->assert->areEquivalent(new AccessRank(AccessRank::Registered), $user->getAccessRank());
		$this->assert->isFalse(empty($user->getPasswordSalt()));
		$this->assert->isFalse(empty($user->getPasswordHash()));
	}

	public function testLogBuffering()
	{
		$this->testSaving();

		$logPath = Logger::getLogPath();
		$x = file_get_contents($logPath);
		$lines = array_filter(explode("\n", $x));
		$this->assert->areEqual(2, count($lines));
	}
}
