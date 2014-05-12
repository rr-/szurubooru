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

		$context = getContext();

		$context->filter = $filter;
		$context->transport->users = $ret->entities;
		$context->transport->paginator = $ret;
	}

	public function genericView($name, $tab = 'favs', $page = 1)
	{
		$user = Api::run(
			new GetUserJob(),
			[
				JobArgs::ARG_USER_NAME => $name,
			]);

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

		$context = getContext();
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

	public function settingsAction($name)
	{
		$this->genericView($name, 'settings');

		$user = getContext()->transport->user;

		Access::assert(new Privilege(
			Privilege::ChangeUserSettings,
			Access::getIdentity($user)));

		$suppliedSafety = InputHelper::get('safety');
		if (!is_array($suppliedSafety))
			$suppliedSafety = [];
		foreach (PostSafety::getAll() as $safety)
			$user->enableSafety($safety, in_array($safety->toInteger(), $suppliedSafety));

		$user->enableEndlessScrolling(InputHelper::get('endless-scrolling'));
		$user->enablePostTagTitles(InputHelper::get('post-tag-titles'));
		$user->enableHidingDislikedPosts(InputHelper::get('hide-disliked-posts'));

		if ($user->getAccessRank()->toInteger() != AccessRank::Anonymous)
			UserModel::save($user);
		if ($user->getId() == Auth::getCurrentUser()->getId())
			Auth::setCurrentUser($user);

		Messenger::message('Browsing settings updated!');
	}

	public function editAction($name)
	{
		$this->genericView($name, 'edit');
		$this->requirePasswordConfirmation();

		if (InputHelper::get('password1') != InputHelper::get('password2'))
			throw new SimpleException('Specified passwords must be the same');

		$args =
		[
			JobArgs::ARG_USER_NAME => $name,
			JobArgs::ARG_NEW_USER_NAME => InputHelper::get('name'),
			JobArgs::ARG_NEW_PASSWORD => InputHelper::get('password1'),
			JobArgs::ARG_NEW_EMAIL => InputHelper::get('email'),
			JobArgs::ARG_NEW_ACCESS_RANK => InputHelper::get('access-rank'),
		];

		$args = array_filter($args);
		$user = Api::run(new EditUserJob(), $args);

		if (Auth::getCurrentUser()->getId() == $user->getId())
			Auth::setCurrentUser($user);

		$message = 'Account settings updated!';
		if (Mailer::getMailCounter() > 0)
			$message .= ' You will be sent an e-mail address confirmation message soon.';

		Messenger::message($message);
	}

	public function deleteAction($name)
	{
		$this->genericView($name, 'delete');
		$this->requirePasswordConfirmation();

		Api::run(new DeleteUserJob(), [
			JobArgs::ARG_USER_NAME => $name]);

		$user = UserModel::tryGetById(Auth::getCurrentUser()->getId());
		if (!$user)
			Auth::logOut();

		\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['StaticPagesController', 'mainPageView']));
		exit;
	}

	public function flagAction($name)
	{
		Api::run(new FlagUserJob(), [JobArgs::ARG_USER_NAME => $name]);
	}

	public function banAction($name)
	{
		Api::run(new ToggleUserBanJob(), [
			JobArgs::ARG_USER_NAME => $name,
			JobArgs::ARG_NEW_STATE => true]);
	}

	public function unbanAction($name)
	{
		Api::run(new ToggleUserBanJob(), [
			JobArgs::ARG_USER_NAME => $name,
			JobArgs::ARG_NEW_STATE => false]);
	}

	public function acceptRegistrationAction($name)
	{
		Api::run(new AcceptUserRegistrationJob(), [
			JobArgs::ARG_USER_NAME => $name]);
	}

	public function toggleSafetyAction($safety)
	{
		$user = Auth::getCurrentUser();

		Access::assert(new Privilege(
			Privilege::ChangeUserSettings,
			Access::getIdentity($user)));

		$safety = new PostSafety($safety);
		$safety->validate();

		$user->enableSafety($safety, !$user->hasEnabledSafety($safety));

		if ($user->getAccessRank()->toInteger() != AccessRank::Anonymous)
			UserModel::save($user);
		Auth::setCurrentUser($user);
	}

	public function registrationView()
	{
		$context = getContext();
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

		if (!getConfig()->registration->needEmailForRegistering and !getConfig()->registration->staffActivation)
		{
			Auth::setCurrentUser($user);
		}

		$message = 'Congratulations, your account was created.';
		if (Mailer::getMailCounter() > 0)
		{
			$message .= ' Please wait for activation e-mail.';
			if (getConfig()->registration->staffActivation)
				$message .= ' After this, your registration must be confirmed by staff.';
		}
		elseif (getConfig()->registration->staffActivation)
			$message .= ' Your registration must be now confirmed by staff.';

		Messenger::message($message);
	}

	public function activationView()
	{
		$context = getContext();
		$context->viewName = 'user-select';
		Assets::setSubTitle('account activation');
	}

	public function activationAction($tokenText)
	{
		$context = getContext();
		$context->viewName = 'message';
		Assets::setSubTitle('account activation');
		$name = InputHelper::get('name');

		if (empty($tokenText))
		{
			Api::run(new ActivateUserEmailJob(), [ JobArgs::ARG_USER_NAME => $name ]);

			Messenger::message('Activation e-mail resent.');
		}
		else
		{
			$user = Api::run(new ActivateUserEmailJob(), [ JobArgs::ARG_TOKEN => $tokenText ]);

			$message = 'Activation completed successfully.';
			if (getConfig()->registration->staffActivation)
				$message .= ' However, your account still must be confirmed by staff.';
			Messenger::message($message);

			if (!getConfig()->registration->staffActivation)
				Auth::setCurrentUser($user);
		}
	}

	public function passwordResetView()
	{
		$context = getContext();
		$context->viewName = 'user-select';
		Assets::setSubTitle('password reset');
	}

	public function passwordResetAction($tokenText)
	{
		$context = getContext();
		$context->viewName = 'message';
		Assets::setSubTitle('password reset');
		$name = InputHelper::get('name');

		if (empty($tokenText))
		{
			Api::run(new PasswordResetJob(), [ JobArgs::ARG_USER_NAME => $name ]);

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
		$user = getContext()->transport->user;
		if (Auth::getCurrentUser()->getId() == $user->getId())
		{
			$suppliedPassword = InputHelper::get('current-password');
			$suppliedPasswordHash = UserModel::hashPassword($suppliedPassword, $user->getPasswordSalt());
			if ($suppliedPasswordHash != $user->getPasswordHash())
				throw new SimpleException('Must supply valid password');
		}
	}
}
