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
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_USER_NAME => $newName,
					JobArgs::ARG_NEW_PASSWORD => 'changed',
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

	public function testCanEditSomething()
	{
		$this->grantAccess('changeUserName.own');
		$user = $this->mockUser();
		$user = $this->assert->isTrue((new EditUserJob())->canEditAnything($user));
	}

	public function testCannotEditAnything()
	{
		$user = $this->mockUser();
		$user = $this->assert->isFalse((new EditUserJob())->canEditAnything($user));
	}
}
