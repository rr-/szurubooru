<?php
class EditUserSettingsJobTest extends AbstractTest
{
	public function testEditing()
	{
		$this->grantAccess('editUserSettings');
		$user = $this->userMocker->mockSingle();

		$expectedSafety = (new PostSafety(PostSafety::Sketchy))->toFlag();
		$user = $this->assert->doesNotThrow(function() use ($user, $expectedSafety)
		{
			return Api::run(
				new EditUserSettingsJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_SETTINGS =>
					[
						UserSettings::SETTING_SAFETY => $expectedSafety,
						UserSettings::SETTING_ENDLESS_SCROLLING => true,
						UserSettings::SETTING_POST_TAG_TITLES => true,
						UserSettings::SETTING_HIDE_DISLIKED_POSTS => true,
					]
				]);
		});

		$settings = $user->getSettings();

		$this->assert->areEqual($expectedSafety, $settings->get(UserSettings::SETTING_SAFETY));
		$this->assert->isTrue($settings->get(UserSettings::SETTING_ENDLESS_SCROLLING));
		$this->assert->isTrue($settings->get(UserSettings::SETTING_POST_TAG_TITLES));
		$this->assert->isTrue($settings->get(UserSettings::SETTING_HIDE_DISLIKED_POSTS));

		$user = $this->assert->doesNotThrow(function() use ($user, $expectedSafety)
		{
			return Api::run(
				new EditUserSettingsJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_SETTINGS =>
					[
						UserSettings::SETTING_ENDLESS_SCROLLING => false,
						UserSettings::SETTING_POST_TAG_TITLES => false,
						UserSettings::SETTING_HIDE_DISLIKED_POSTS => false,
					]
				]);
		});

		$settings = $user->getSettings();

		$this->assert->isFalse($settings->get(UserSettings::SETTING_ENDLESS_SCROLLING));
		$this->assert->isFalse($settings->get(UserSettings::SETTING_POST_TAG_TITLES));
		$this->assert->isFalse($settings->get(UserSettings::SETTING_HIDE_DISLIKED_POSTS));
	}

	public function testSettingAdditional()
	{
		$this->grantAccess('editUserSettings');
		$user = $this->userMocker->mockSingle();

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new EditUserSettingsJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_SETTINGS =>
					[
						'additional' => 'rubbish',
					]
				]);
		});

		$settings = $user->getSettings();

		$expectedSafety = (new PostSafety(PostSafety::Safe))->toFlag();
		$this->assert->areEqual($expectedSafety, $settings->get(UserSettings::SETTING_SAFETY));
		$this->assert->isTrue($settings->get(UserSettings::SETTING_ENDLESS_SCROLLING));
		$this->assert->isFalse($settings->get(UserSettings::SETTING_POST_TAG_TITLES));
		$this->assert->isFalse($settings->get(UserSettings::SETTING_HIDE_DISLIKED_POSTS));
		$this->assert->areEqual('rubbish', $settings->get('additional'));
	}

	public function testSettingBadValues()
	{
		$this->grantAccess('editUserSettings');
		$user = $this->userMocker->mockSingle();

		$user = $this->assert->doesNotThrow(function() use ($user)
		{
			return Api::run(
				new EditUserSettingsJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_SETTINGS =>
					[
						UserSettings::SETTING_SAFETY => 'rubbish',
						UserSettings::SETTING_ENDLESS_SCROLLING => 'rubbish',
						UserSettings::SETTING_POST_TAG_TITLES => 'rubbish',
						UserSettings::SETTING_HIDE_DISLIKED_POSTS => 'rubbish',
					]
				]);
		});

		$settings = $user->getSettings();
		$expectedSafety = (new PostSafety(PostSafety::Safe))->toFlag();
		$this->assert->areEqual($expectedSafety, $settings->get(UserSettings::SETTING_SAFETY));
		$this->assert->isTrue($settings->get(UserSettings::SETTING_ENDLESS_SCROLLING));
		$this->assert->isFalse($settings->get(UserSettings::SETTING_POST_TAG_TITLES));
		$this->assert->isFalse($settings->get(UserSettings::SETTING_HIDE_DISLIKED_POSTS));
	}

	public function testSettingTooLongData()
	{
		$this->grantAccess('editUserSettings');
		$user = $this->userMocker->mockSingle();

		$this->assert->throws(function() use ($user)
		{
			return Api::run(
				new EditUserSettingsJob(),
				[
					JobArgs::ARG_USER_NAME => $user->getName(),
					JobArgs::ARG_NEW_SETTINGS =>
					[
						'additional' => str_repeat('rubbish', 50),
					]]);
		}, 'Too much data');
	}
}
