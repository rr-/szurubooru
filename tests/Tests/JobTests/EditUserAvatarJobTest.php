<?php
class EditUserAvatarJobTest extends AbstractTest
{
	public function testGravatar()
	{
		$this->grantAccess('editUserAvatar');
		$user = $this->userMocker->mockSingle();

		$this->assert->areEqual(UserAvatarStyle::Gravatar, $user->getAvatarStyle()->toInteger());

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new EditUserAvatarJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_AVATAR_STYLE => UserAvatarStyle::Gravatar,
				]);
		});

		$this->assert->areEqual(UserAvatarStyle::Gravatar, $user->getAvatarStyle()->toInteger());

		$hash = md5($user->getPasswordSalt() . $user->getName());
		$this->assert->isTrue(strpos($user->getAvatarUrl(), $hash) !== false);

		$mail = 'postmaster@mordor.cx';
		$user->setConfirmedEmail($mail);
		UserModel::save($user);

		$hash = md5($mail);
		$this->assert->isTrue(strpos($user->getAvatarUrl(), $hash) !== false);
	}

	public function testEmpty()
	{
		$this->grantAccess('editUserAvatar');
		$user = $this->userMocker->mockSingle();

		$this->assert->areEqual(UserAvatarStyle::Gravatar, $user->getAvatarStyle()->toInteger());

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new EditUserAvatarJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_AVATAR_STYLE => UserAvatarStyle::None,
				]);
		});

		$this->assert->areEqual(UserAvatarStyle::None, $user->getAvatarStyle()->toInteger());

		$hash = md5($user->getPasswordSalt() . $user->getName());
		$this->assert->isTrue(strpos($user->getAvatarUrl(), $hash) === false);

		$mail = 'postmaster@mordor.cx';
		$user->setConfirmedEmail($mail);
		UserModel::save($user);

		$hash = md5($mail);
		$this->assert->isTrue(strpos($user->getAvatarUrl(), $hash) === false);
	}

	public function testCustom()
	{
		$this->grantAccess('editUserAvatar');
		$user = $this->userMocker->mockSingle();

		$this->assert->areEqual(UserAvatarStyle::Gravatar, $user->getAvatarStyle()->toInteger());

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new EditUserAvatarJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_AVATAR_STYLE => UserAvatarStyle::Custom,
					JobArgs::ARG_NEW_AVATAR_CONTENT => new ApiFileInput($this->testSupport->getPath('image.jpg'), 'image.jpg')
				]);
		});

		$this->assert->areEqual(UserAvatarStyle::Custom, $user->getAvatarStyle()->toInteger());

		$hash = md5($user->getPasswordSalt() . $user->getName());
		$this->assert->isTrue(strpos($user->getAvatarUrl(), $hash) === false);
		$this->assert->isTrue(strpos($user->getAvatarUrl(32), '32') !== false);
	}
}
