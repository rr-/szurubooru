<?php
class UserController
{
	public function listView($filter = 'order:alpha,asc', $page = 1)
	{
		$ret = Api::run(
			new ListUsersJob(),
			[
				ListUsersJob::PAGE_NUMBER => $page,
				ListUsersJob::QUERY => $filter,
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
				GetUserJob::USER_NAME => $name,
			]);

		$flagged = in_array(TextHelper::reprUser($user), SessionHelper::get('flagged', []));

		$context = getContext();
		$context->flagged = $flagged;
		$context->transport->tab = $tab;
		$context->transport->user = $user;
		$context->handleExceptions = true;
		$context->viewName = 'user-view';

		if ($tab == 'uploads')
			$query = 'submit:' . $user->name;
		elseif ($tab == 'favs')
			$query = 'fav:' . $user->name;

		if (isset($query))
		{
			$ret = Api::run(
				new ListPostsJob(),
				[
					ListPostsJob::PAGE_NUMBER => $page,
					ListPostsJob::QUERY => $query
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

		Access::assert(
			Privilege::ChangeUserSettings,
			Access::getIdentity($user));

		$suppliedSafety = InputHelper::get('safety');
		if (!is_array($suppliedSafety))
			$suppliedSafety = [];
		foreach (PostSafety::getAll() as $safety)
			$user->enableSafety($safety, in_array($safety, $suppliedSafety));

		$user->enableEndlessScrolling(InputHelper::get('endless-scrolling'));
		$user->enablePostTagTitles(InputHelper::get('post-tag-titles'));
		$user->enableHidingDislikedPosts(InputHelper::get('hide-disliked-posts'));

		if ($user->accessRank != AccessRank::Anonymous)
			UserModel::save($user);
		if ($user->id == Auth::getCurrentUser()->id)
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
			EditUserNameJob::USER_NAME => $name,
			EditUserNameJob::NEW_USER_NAME => InputHelper::get('name'),
			EditUserPasswordJob::NEW_PASSWORD => InputHelper::get('password1'),
			EditUserEmailJob::NEW_EMAIL => InputHelper::get('email'),
			EditUserAccessRankJob::NEW_ACCESS_RANK => InputHelper::get('access-rank'),
		];

		$args = array_filter($args);
		$user = Api::run(new EditUserJob(), $args);

		if (Auth::getCurrentUser()->id == $user->id)
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
			DeleteUserJob::USER_NAME => $name]);

		$user = UserModel::findById(Auth::getCurrentUser()->id, false);
		if (!$user)
			Auth::logOut();

		\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['StaticPagesController', 'mainPageView']));
		exit;
	}

	public function flagAction($name)
	{
		Api::run(new FlagUserJob(), [FlagUserJob::USER_NAME => $name]);
	}

	public function banAction($name)
	{
		Api::run(new ToggleUserBanJob(), [
			ToggleUserBanJob::USER_NAME => $name,
			ToggleUserBanJob::STATE => true]);
	}

	public function unbanAction($name)
	{
		Api::run(new ToggleUserBanJob(), [
			ToggleUserBanJob::USER_NAME => $name,
			ToggleUserBanJob::STATE => false]);
	}

	public function acceptRegistrationAction($name)
	{
		Api::run(new AcceptUserRegistrationJob(), [
			AcceptUserRegistrationJob::USER_NAME => $name]);
	}

	public function toggleSafetyAction($safety)
	{
		$user = Auth::getCurrentUser();

		Access::assert(
			Privilege::ChangeUserSettings,
			Access::getIdentity($user));

		if (!in_array($safety, PostSafety::getAll()))
			throw new SimpleExcetpion('Invalid safety');

		$user->enableSafety($safety, !$user->hasEnabledSafety($safety));

		if ($user->accessRank != AccessRank::Anonymous)
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
			EditUserNameJob::NEW_USER_NAME => InputHelper::get('name'),
			EditUserPasswordJob::NEW_PASSWORD => InputHelper::get('password1'),
			EditUserEmailJob::NEW_EMAIL => InputHelper::get('email'),
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

	public function activationAction($token)
	{
		$context = getContext();
		$context->viewName = 'message';
		Assets::setSubTitle('account activation');

		if (empty($token))
		{
			$name = InputHelper::get('name');
			$user = UserModel::findByNameOrEmail($name);
			if (empty($user->emailUnconfirmed))
			{
				if (!empty($user->emailConfirmed))
					throw new SimpleException('E-mail was already confirmed; activation skipped');
				else
					throw new SimpleException('This user has no e-mail specified; activation cannot proceed');
			}
			EditUserEmailJob::sendEmail($user);
			Messenger::message('Activation e-mail resent.');
		}
		else
		{
			$dbToken = TokenModel::findByToken($token);
			TokenModel::checkValidity($dbToken);

			$dbUser = $dbToken->getUser();
			if (empty($dbUser->emailConfirmed))
			{
				$dbUser->emailConfirmed = $dbUser->emailUnconfirmed;
				$dbUser->emailUnconfirmed = null;
			}
			$dbToken->used = true;
			TokenModel::save($dbToken);
			UserModel::save($dbUser);

			LogHelper::log('{subject} just activated account', ['subject' => TextHelper::reprUser($dbUser)]);
			$message = 'Activation completed successfully.';
			if (getConfig()->registration->staffActivation)
				$message .= ' However, your account still must be confirmed by staff.';
			Messenger::message($message);

			if (!getConfig()->registration->staffActivation)
			{
				Auth::setCurrentUser($dbUser);
			}
		}
	}

	public function passwordResetView()
	{
		$context = getContext();
		$context->viewName = 'user-select';
		Assets::setSubTitle('password reset');
	}

	public function passwordResetAction($token)
	{
		$context = getContext();
		$context->viewName = 'message';
		Assets::setSubTitle('password reset');

		if (empty($token))
		{
			$name = InputHelper::get('name');
			$user = UserModel::findByNameOrEmail($name);
			if (empty($user->emailConfirmed))
				throw new SimpleException('This user has no e-mail confirmed; password reset cannot proceed');

			self::sendPasswordResetConfirmation($user);
			Messenger::message('E-mail sent. Follow instructions to reset password.');
		}
		else
		{
			$dbToken = TokenModel::findByToken($token);
			TokenModel::checkValidity($dbToken);

			$alphabet = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
			$randomPassword = join('', array_map(function($x) use ($alphabet)
			{
				return $alphabet[$x];
			}, array_rand($alphabet, 8)));

			$dbUser = $dbToken->getUser();
			$dbUser->passHash = UserModel::hashPassword($randomPassword, $dbUser->passSalt);
			$dbToken->used = true;
			TokenModel::save($dbToken);
			UserModel::save($dbUser);

			LogHelper::log('{subject} just reset password', ['subject' => TextHelper::reprUser($dbUser)]);
			$message = 'Password reset successful. Your new password is **' . $randomPassword . '**.';
			Messenger::message($message);

			Auth::setCurrentUser($dbUser);
		}
	}

	private static function sendPasswordResetConfirmation($user)
	{
		$regConfig = getConfig()->registration;

		$mail = new Mail();
		$mail->body = $regConfig->passwordResetEmailBody;
		$mail->subject = $regConfig->passwordResetEmailSubject;
		$mail->senderName = $regConfig->passwordResetEmailSenderName;
		$mail->senderEmail = $regConfig->passwordResetEmailSenderEmail;
		$mail->recipientEmail = $user->emailConfirmed;

		return Mailer::sendMailWithTokenLink(
			$user,
			['UserController', 'passwordResetAction'],
			$mail);
	}

	private function requirePasswordConfirmation()
	{
		$user = getContext()->transport->user;
		if (Auth::getCurrentUser()->id == $user->id)
		{
			$suppliedPassword = InputHelper::get('current-password');
			$suppliedPasswordHash = UserModel::hashPassword($suppliedPassword, $user->passSalt);
			if ($suppliedPasswordHash != $user->passHash)
				throw new SimpleException('Must supply valid password');
		}
	}
}
