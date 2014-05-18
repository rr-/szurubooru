<?php
class EditUserNameJobTest extends AbstractTest
{
	public function testEditing()
	{
		$this->grantAccess('editUserName');
		$user = $this->userMocker->mockSingle();

		$newName = uniqid();

		$this->assert->areNotEqual($newName, $user->getName());

		$user = $this->assert->doesNotThrow(function() use ($user, $newName)
		{
			return Api::run(
				new EditUserNameJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_USER_NAME => $newName,
				]);
		});

		$this->assert->areEqual($newName, $user->getName());
	}

	public function testTooShortName()
	{
		$this->grantAccess('editUserName');
		$user = $this->userMocker->mockSingle();

		$newName = str_repeat('a', Core::getConfig()->registration->userNameMinLength - 1);

		$this->assert->throws(function() use ($user, $newName)
		{
			Api::run(
				new EditUserNameJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_USER_NAME => $newName,
				]);
		}, 'user name must have at least');
	}

	public function testTooLongName()
	{
		$this->grantAccess('editUserName');
		$user = $this->userMocker->mockSingle();

		$newName = str_repeat('a', Core::getConfig()->registration->userNameMaxLength + 1);

		$this->assert->throws(function() use ($user, $newName)
		{
			Api::run(
				new EditUserNameJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_USER_NAME => $newName,
				]);
		}, 'user name must have at most');
	}

	public function testInvalidName()
	{
		$this->grantAccess('editUserName');
		$user = $this->userMocker->mockSingle();

		$newName = 'ble/ble';

		$this->assert->throws(function() use ($user, $newName)
		{
			Api::run(
				new EditUserNameJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_USER_NAME => $newName,
				]);
		}, 'user name contains invalid characters');
	}

	public function testChangingToExistingDenial()
	{
		$this->grantAccess('editUserName');
		list ($user, $otherUser)
			= $this->userMocker->mockMultiple(2);

		$newName = $otherUser->getName();
		$this->assert->areNotEqual($newName, $user->getName());

		$this->assert->throws(function() use ($user, $newName)
		{
			Api::run(
				new EditUserNameJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_USER_NAME => $newName,
				]);
		}, 'User with this name is already registered');

		$this->assert->areNotEqual($newName, $user->getName());
	}
}
