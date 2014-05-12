<?php
class EditUserPasswordJobTest extends AbstractTest
{
	public function testEditing()
	{
		$this->testValidPassword('flintstone');
	}

	public function testVeryLongPassword()
	{
		$this->testValidPassword(str_repeat('flintstone', 100));
	}

	public function testTooShortPassword()
	{
		$this->grantAccess('changeUserPassword');
		$user = $this->mockUser();

		$newPassword = str_repeat('a', getConfig()->registration->passMinLength - 1);
		$oldPasswordHash = $user->getPasswordHash();

		$this->assert->throws(function() use ($user, $newPassword)
		{
			return Api::run(
				new EditUserPasswordJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_PASSWORD => $newPassword,
				]);
		}, 'Password must have at least');

		$this->assert->areEqual($oldPasswordHash, $user->getPasswordHash());
	}

	private function testValidPassword($newPassword)
	{
		$this->grantAccess('changeUserPassword');
		$user = $this->mockUser();

		$newPasswordHash = UserModel::hashPassword($newPassword, $user->getPasswordSalt());

		$user = $this->assert->doesNotThrow(function() use ($user, $newPassword)
		{
			return Api::run(
				new EditUserPasswordJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_PASSWORD => $newPassword,
				]);
		});

		$this->assert->areEqual($newPasswordHash, $user->getPasswordHash());

		getConfig()->registration->needEmailForRegistering = false;
		$this->assert->doesNotThrow(function() use ($user, $newPassword)
		{
			Auth::login($user->getName(), $newPassword, false);
		});
		$this->assert->isTrue(Auth::isLoggedIn());
	}
}
