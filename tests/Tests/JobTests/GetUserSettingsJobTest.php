<?php
class GetUserSettingsJobTest extends AbstractTest
{
	public function testRetrieving()
	{
		$this->grantAccess('editUserSettings');
		$user = $this->userMocker->mockSingle();

		$settings = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new GetUserSettingsJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$expectedSafety = (new PostSafety(PostSafety::Safe))->toFlag();
		$this->assert->areEqual($expectedSafety, $settings[UserSettings::SETTING_SAFETY]);

		$this->assert->isTrue($settings[UserSettings::SETTING_ENDLESS_SCROLLING]);
		$this->assert->isFalse($settings[UserSettings::SETTING_POST_TAG_TITLES]);
		$this->assert->isFalse($settings[UserSettings::SETTING_HIDE_DISLIKED_POSTS]);
	}

	public function testSwitchingSafety()
	{
		$this->grantAccess('editUserSettings');
		$user = $this->userMocker->mockSingle();

		$user->getSettings()->enableSafety(new PostSafety(PostSafety::Sketchy), true);
		UserModel::save($user);

		$settings = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new GetUserSettingsJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$expectedSafety =
			((new PostSafety(PostSafety::Safe))->toFlag()
			| (new PostSafety(PostSafety::Sketchy))->toFlag());

		$this->assert->areEqual($expectedSafety, $settings[UserSettings::SETTING_SAFETY]);

		$this->assert->isTrue($settings[UserSettings::SETTING_ENDLESS_SCROLLING]);
		$this->assert->isFalse($settings[UserSettings::SETTING_POST_TAG_TITLES]);
		$this->assert->isFalse($settings[UserSettings::SETTING_HIDE_DISLIKED_POSTS]);
	}

	public function testSwitchingSafety2()
	{
		$this->grantAccess('editUserSettings');
		$user = $this->userMocker->mockSingle();

		$user->getSettings()->enableSafety(new PostSafety(PostSafety::Sketchy), true);
		$user->getSettings()->enableSafety(new PostSafety(PostSafety::Safe), false);
		UserModel::save($user);

		$settings = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new GetUserSettingsJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
				]);
		});

		$expectedSafety = (new PostSafety(PostSafety::Sketchy))->toFlag();
		$this->assert->areEqual($expectedSafety, $settings[UserSettings::SETTING_SAFETY]);

		$this->assert->isTrue($settings[UserSettings::SETTING_ENDLESS_SCROLLING]);
		$this->assert->isFalse($settings[UserSettings::SETTING_POST_TAG_TITLES]);
		$this->assert->isFalse($settings[UserSettings::SETTING_HIDE_DISLIKED_POSTS]);
	}
}
