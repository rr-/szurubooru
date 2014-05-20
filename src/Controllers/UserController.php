<?php
class UserController extends AbstractController
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
		$this->renderView('user-list');
	}

	public function genericView($identifier, $tab = 'favs', $page = 1)
	{
		$this->prepareGenericView($identifier, $tab, $page);
		$this->renderView('user-view');
	}

	public function settingsAction($identifier)
	{
		$this->prepareGenericView($identifier, 'settings');

		try
		{
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

			Messenger::success('Browsing settings updated!');
		}
		catch (SimpleException $e)
		{
			\Chibi\Util\Headers::setCode(400);
			Messenger::fail($e->getMessage());
		}

		if ($this->isAjax())
			$this->renderAjax();
		else
			$this->renderView('user-view');
	}

	public function editAction($identifier)
	{
		$this->prepareGenericView($identifier, 'edit');

		try
		{
			$this->requirePasswordConfirmation();

			if (InputHelper::get('password1') != InputHelper::get('password2'))
				throw new SimpleException('Specified passwords must be the same');

			$args =
			[
				JobArgs::ARG_NEW_USER_NAME => InputHelper::get('name'),
				JobArgs::ARG_NEW_PASSWORD => InputHelper::get('password1'),
				JobArgs::ARG_NEW_EMAIL => InputHelper::get('email'),
				JobArgs::ARG_NEW_ACCESS_RANK => InputHelper::get('access-rank'),
				Jobargs::ARG_NEW_AVATAR_STYLE => InputHelper::get('avatar-style'),
			];

			if (!empty($_FILES['avatar-content']['name']))
			{
				$file = $_FILES['avatar-content'];
				TransferHelper::handleUploadErrors($file);

				$args[JobArgs::ARG_NEW_AVATAR_CONTENT] = new ApiFileInput(
					$file['tmp_name'],
					$file['name']);
			}

			$args = $this->appendUserIdentifierArgument($args, $identifier);

			$args = array_filter($args);
			$user = Api::run(new EditUserJob(), $args);

			Core::getContext()->transport->user = $user;
			if (Auth::getCurrentUser()->getId() == $user->getId())
				Auth::setCurrentUser($user);

			$message = 'Account settings updated!';
			if (Mailer::getMailCounter() > 0)
				$message .= ' You will be sent an e-mail address confirmation message soon.';

			Messenger::success($message);
		}
		catch (SimpleException $e)
		{
			\Chibi\Util\Headers::setCode(400);
			Messenger::fail($e->getMessage());
		}

		if ($this->isAjax())
			$this->renderAjax();
		else
			$this->renderView('user-view');
	}

	public function deleteAction($identifier)
	{
		$this->prepareGenericView($identifier, 'delete');

		try
		{
			$this->requirePasswordConfirmation();

			Api::run(
				new DeleteUserJob(),
				$this->appendUserIdentifierArgument([], $identifier));

			$user = UserModel::tryGetById(Auth::getCurrentUser()->getId());
			if (!$user)
				Auth::logOut();

			$this->redirectToMainPage();
		}
		catch (SimpleException $e)
		{
			\Chibi\Util\Headers::setCode(400);
			Messenger::fail($e->getMessage());

			if ($this->isAjax())
				$this->renderAjax();
			else
				$this->renderView('user-view');
		}
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
		$this->redirectToLastVisitedUrl();
	}

	public function flagAction($identifier)
	{
		Api::run(
			new FlagUserJob(),
			$this->appendUserIdentifierArgument([], $identifier));
		$this->redirectToGenericView($identifier);
	}

	public function banAction($identifier)
	{
		Api::run(
			new ToggleUserBanJob(),
			$this->appendUserIdentifierArgument([
				JobArgs::ARG_NEW_STATE => true
			], $identifier));
		$this->redirectToGenericView($identifier);
	}

	public function unbanAction($identifier)
	{
		Api::run(
			new ToggleUserBanJob(),
			$this->appendUserIdentifierArgument([
				JobArgs::ARG_NEW_STATE => true
			], $identifier));
		$this->redirectToGenericView($identifier);
	}

	public function acceptRegistrationAction($identifier)
	{
		Api::run(
			new AcceptUserRegistrationJob(),
			$this->appendUserIdentifierArgument([], $identifier));
		$this->redirectToGenericView($identifier);
	}

	public function registrationView()
	{
		if (Auth::isLoggedIn())
			$this->redirectToMainPage();
		$this->renderView('user-registration');
	}

	public function registrationAction()
	{
		try
		{
			if (InputHelper::get('password1') != InputHelper::get('password2'))
				throw new SimpleException('Specified passwords must be the same');

			$user = Api::run(new AddUserJob(),
			[
				JobArgs::ARG_NEW_USER_NAME => InputHelper::get('name'),
				JobArgs::ARG_NEW_PASSWORD => InputHelper::get('password1'),
				JobArgs::ARG_NEW_EMAIL => InputHelper::get('email'),
			]);

			if (!$this->isAnyAccountActivationNeeded())
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

			Messenger::success($message);
		}
		catch (SimpleException $e)
		{
			\Chibi\Util\Headers::setCode(400);
			Messenger::fail($e->getMessage());
		}

		$this->renderView('user-registration');
	}

	public function activationView()
	{
		$this->assets->setSubTitle('account activation');
		$this->renderView('user-select');
	}

	public function activationAction($tokenText)
	{
		$this->assets->setSubTitle('account activation');
		$identifier = InputHelper::get('identifier');

		try
		{
			if (empty($tokenText))
			{
				Api::run(
					new ActivateUserEmailJob(),
					$this->appendUserIdentifierArgument([], $identifier));

				Messenger::success('Activation e-mail resent.');
			}
			else
			{
				$user = Api::run(new ActivateUserEmailJob(), [
					JobArgs::ARG_TOKEN => $tokenText ]);

				$message = 'Activation completed successfully.';
				if (Core::getConfig()->registration->staffActivation)
					$message .= ' However, your account still must be confirmed by staff.';
				Messenger::success($message);

				if (!Core::getConfig()->registration->staffActivation)
					Auth::setCurrentUser($user);
			}
		}
		catch (SimpleException $e)
		{
			\Chibi\Util\Headers::setCode(400);
			Messenger::fail($e->getMessage());
		}

		$this->renderView('message');
	}

	public function passwordResetView()
	{
		$this->assets->setSubTitle('password reset');
		$this->renderView('user-select');
	}

	public function passwordResetAction($tokenText)
	{
		$this->assets->setSubTitle('password reset');
		$identifier = InputHelper::get('identifier');

		try
		{
			if (empty($tokenText))
			{
				Api::run(
					new PasswordResetJob(),
					$this->appendUserIdentifierArgument([], $identifier));

				Messenger::success('E-mail sent. Follow instructions to reset password.');
			}
			else
			{
				$ret = Api::run(new PasswordResetJob(), [ JobArgs::ARG_TOKEN => $tokenText ]);

				Messenger::success(sprintf(
					'Password reset successful. Your new password is **%s**.',
					$ret->newPassword));

				Auth::setCurrentUser($ret->user);
			}
		}
		catch (SimpleException $e)
		{
			\Chibi\Util\Headers::setCode(400);
			Messenger::fail($e->getMessage());
		}

		$this->renderView('message');
	}


	private function prepareGenericView($identifier, $tab, $page = 1)
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
				Privilege::EditUserSettings,
				Access::getIdentity($user)));
		}
		elseif ($tab == 'edit' and !(new EditUserJob)->canEditAnything(Auth::getCurrentUser()))
			Access::fail();

		$context = Core::getContext();
		$context->flagged = $flagged;
		$context->transport->tab = $tab;
		$context->transport->user = $user;

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


	private function isAnyAccountActivationNeeded()
	{
		$config = Core::getConfig();
		return ($config->registration->needEmailForRegistering
			or $config->registration->staffActivation);
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

	private function redirectToMainPage()
	{
		$this->redirect(\Chibi\Router::linkTo(['StaticPagesController', 'mainPageView']));
		exit;
	}

	private function redirectToGenericView($identifier)
	{
		$this->redirect(\Chibi\Router::linkTo(
			['UserController', 'genericView'],
			['identifier' => $identifier]));
	}
}
