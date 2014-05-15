<?php
class UserController
{
	public function listView($filter = 'order:alpha,asc', $page = 1)
	{
		$ret = Api::run(
			new ListUsersJob(),
			[
				JobArgs::ARG_PAGE_NUMBER => $page,
				JobArgs::ARG_QUERY => $filter,
			]);

		$context = Core::getContext();

		$context->filter = $filter;
		$context->transport->users = $ret->entities;
		$context->transport->paginator = $ret;
	}

	public function genericView($identifier, $tab = 'favs', $page = 1)
	{
		$user = Api::run(
			new GetUserJob(),
			$this->appendUserIdentifierArgument([], $identifier));

		$flagged = in_array(TextHelper::reprUser($user), SessionHelper::get('flagged', []));

		if ($tab == 'uploads')
			$query = 'submit:' . $user->getName();
		elseif ($tab == 'favs')
			$query = 'fav:' . $user->getName();

		elseif ($tab == 'delete')
		{
			Access::assert(new Privilege(
				Privilege::DeleteUser,
				Access::getIdentity($user)));
		}
		elseif ($tab == 'settings')
		{
			Access::assert(new Privilege(
				Privilege::ChangeUserSettings,
				Access::getIdentity($user)));
		}
		elseif ($tab == 'edit' and !(new EditUserJob)->canEditAnything(Auth::getCurrentUser()))
			Access::fail();

		$context = Core::getContext();
		$context->flagged = $flagged;
		$context->transport->tab = $tab;
		$context->transport->user = $user;
		$context->handleExceptions = true;
		$context->viewName = 'user-view';

		if (isset($query))
		{
			$ret = Api::run(
				new ListPostsJob(),
				[
					JobArgs::ARG_PAGE_NUMBER => $page,
					JobArgs::ARG_QUERY => $query
				]);

			$context->transport->posts = $ret->entities;
			$context->transport->paginator = $ret;
			$context->transport->lastSearchQuery = $query;
		}
	}

	public function settingsAction($identifier)
	{
		$this->genericView($identifier, 'settings');

		$suppliedSafety = InputHelper::get('safety');
		$desiredSafety = PostSafety::makeFlags($suppliedSafety);

		$user = Api::run(
			new EditUserSettingsJob(),
			$this->appendUserIdentifierArgument(
			[
				JobArgs::ARG_NEW_SETTINGS =>
				[
					UserSettings::SETTING_SAFETY => $desiredSafety,
					UserSettings::SETTING_ENDLESS_SCROLLING => InputHelper::get('endless-scrolling'),
					UserSettings::SETTING_POST_TAG_TITLES => InputHelper::get('post-tag-titles'),
					UserSettings::SETTING_HIDE_DISLIKED_POSTS => InputHelper::get('hide-disliked-posts'),
				]
			], $identifier));

		Core::getContext()->transport->user = $user;
		if ($user->getId() == Auth::getCurrentUser()->getId())
			Auth::setCurrentUser($user);

		Messenger::message('Browsing settings updated!');
	}

	public function toggleSafetyAction($safety)
	{
		$safety = new PostSafety($safety);
		$safety->validate();

		$user = Auth::getCurrentUser();
		$user->getSettings()->enableSafety($safety, !$user->getSettings()->hasEnabledSafety($safety));
		$desiredSafety = $user->getSettings()->get(UserSettings::SETTING_SAFETY);

		$user = Api::run(
			new EditUserSettingsJob(),
			[
				JobArgs::ARG_USER_ENTITY => Auth::getCurrentUser(),
				JobArgs::ARG_NEW_SETTINGS => [UserSettings::SETTING_SAFETY => $desiredSafety],
			]);

		Auth::setCurrentUser($user);
	}

	public function editAction($identifier)
	{
		$this->genericView($identifier, 'edit');
		$this->requirePasswordConfirmation();

		if (InputHelper::get('password1') != InputHelper::get('password2'))
			throw new SimpleException('Specified passwords must be the same');

		$args =
		[
			JobArgs::ARG_NEW_USER_NAME => InputHelper::get('name'),
			JobArgs::ARG_NEW_PASSWORD => InputHelper::get('password1'),
			JobArgs::ARG_NEW_EMAIL => InputHelper::get('email'),
			JobArgs::ARG_NEW_ACCESS_RANK => InputHelper::get('access-rank'),
		];
		$args = $this->appendUserIdentifierArgument($args, $identifier);

		$args = array_filter($args);
		$user = Api::run(new EditUserJob(), $args);

		if (Auth::getCurrentUser()->getId() == $user->getId())
			Auth::setCurrentUser($user);

		$message = 'Account settings updated!';
		if (Mailer::getMailCounter() > 0)
			$message .= ' You will be sent an e-mail address confirmation message soon.';

		Messenger::message($message);
	}

	public function deleteAction($identifier)
	{
		$this->genericView($identifier, 'delete');
		$this->requirePasswordConfirmation();

		Api::run(
			new DeleteUserJob(),
			$this->appendUserIdentifierArgument([], $identifier));

		$user = UserModel::tryGetById(Auth::getCurrentUser()->getId());
		if (!$user)
			Auth::logOut();

		\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['StaticPagesController', 'mainPageView']));
		exit;
	}

	public function flagAction($identifier)
	{
		Api::run(
			new FlagUserJob(),
			$this->appendUserIdentifierArgument([], $identifier));
	}

	public function banAction($identifier)
	{
		Api::run(
			new ToggleUserBanJob(),
			$this->appendUserIdentifierArgument([
				JobArgs::ARG_NEW_STATE => true
			], $identifier));
	}

	public function unbanAction($identifier)
	{
		Api::run(
			new ToggleUserBanJob(),
			$this->appendUserIdentifierArgument([
				JobArgs::ARG_NEW_STATE => true
			], $identifier));
	}

	public function acceptRegistrationAction($identifier)
	{
		Api::run(
			new AcceptUserRegistrationJob(),
			$this->appendUserIdentifierArgument([], $identifier));
	}

	public function registrationView()
	{
		$context = Core::getContext();
		$context->handleExceptions = true;

		//check if already logged in
		if (Auth::isLoggedIn())
		{
			\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['StaticPagesController', 'mainPageView']));
			exit;
		}
	}

	public function registrationAction()
	{
		$this->registrationView();

		if (InputHelper::get('password1') != InputHelper::get('password2'))
			throw new SimpleException('Specified passwords must be the same');

		$user = Api::run(new AddUserJob(),
		[
			JobArgs::ARG_NEW_USER_NAME => InputHelper::get('name'),
			JobArgs::ARG_NEW_PASSWORD => InputHelper::get('password1'),
			JobArgs::ARG_NEW_EMAIL => InputHelper::get('email'),
		]);

		if (!Core::getConfig()->registration->needEmailForRegistering and !Core::getConfig()->registration->staffActivation)
		{
			Auth::setCurrentUser($user);
		}

		$message = 'Congratulations, your account was created.';
		if (Mailer::getMailCounter() > 0)
		{
			$message .= ' Please wait for activation e-mail.';
			if (Core::getConfig()->registration->staffActivation)
				$message .= ' After this, your registration must be confirmed by staff.';
		}
		elseif (Core::getConfig()->registration->staffActivation)
			$message .= ' Your registration must be now confirmed by staff.';

		Messenger::message($message);
	}

	public function activationView()
	{
		$context = Core::getContext();
		$context->viewName = 'user-select';
		Assets::setSubTitle('account activation');
	}

	public function activationAction($tokenText)
	{
		$context = Core::getContext();
		$context->viewName = 'message';
		Assets::setSubTitle('account activation');
		$identifier = InputHelper::get('identifier');

		if (empty($tokenText))
		{
			Api::run(
				new ActivateUserEmailJob(),
				$this->appendUserIdentifierArgument([], $identifier));

			Messenger::message('Activation e-mail resent.');
		}
		else
		{
			$user = Api::run(new ActivateUserEmailJob(), [
				JobArgs::ARG_TOKEN => $tokenText ]);

			$message = 'Activation completed successfully.';
			if (Core::getConfig()->registration->staffActivation)
				$message .= ' However, your account still must be confirmed by staff.';
			Messenger::message($message);

			if (!Core::getConfig()->registration->staffActivation)
				Auth::setCurrentUser($user);
		}
	}

	public function passwordResetView()
	{
		$context = Core::getContext();
		$context->viewName = 'user-select';
		Assets::setSubTitle('password reset');
	}

	public function passwordResetAction($tokenText)
	{
		$context = Core::getContext();
		$context->viewName = 'message';
		Assets::setSubTitle('password reset');
		$identifier = InputHelper::get('identifier');

		if (empty($tokenText))
		{
			Api::run(
				new PasswordResetJob(),
				$this->appendUserIdentifierArgument([], $identifier));

			Messenger::message('E-mail sent. Follow instructions to reset password.');
		}
		else
		{
			$ret = Api::run(new PasswordResetJob(), [ JobArgs::ARG_TOKEN => $tokenText ]);

			Messenger::message(sprintf(
				'Password reset successful. Your new password is **%s**.',
				$ret->newPassword));

			Auth::setCurrentUser($ret->user);
		}
	}

	private function requirePasswordConfirmation()
	{
		$user = Core::getContext()->transport->user;
		if (Auth::getCurrentUser()->getId() == $user->getId())
		{
			$suppliedPassword = InputHelper::get('current-password');
			$suppliedPasswordHash = UserModel::hashPassword($suppliedPassword, $user->getPasswordSalt());
			if ($suppliedPasswordHash != $user->getPasswordHash())
				throw new SimpleException('Must supply valid password');
		}
	}

	private function appendUserIdentifierArgument(array $arguments, $userIdentifier)
	{
		if (strpos($userIdentifier, '@') !== false)
			$arguments[JobArgs::ARG_USER_EMAIL] = $userIdentifier;
		else
			$arguments[JobArgs::ARG_USER_NAME] = $userIdentifier;
		return $arguments;
	}
}
