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

		$user = getContext()->transport->user;

		$suppliedCurrentPassword = InputHelper::get('current-password');
		$suppliedName = InputHelper::get('name');
		$suppliedPassword1 = InputHelper::get('password1');
		$suppliedPassword2 = InputHelper::get('password2');
		$suppliedEmail = InputHelper::get('email');
		$suppliedAccessRank = InputHelper::get('access-rank');

		$confirmMail = false;
		LogHelper::bufferChanges();

		if ($suppliedName != '' and $suppliedName != $user->name)
		{
			Access::assert(
				Privilege::ChangeUserName,
				Access::getIdentity($user));

			$suppliedName = UserModel::validateUserName($suppliedName);
			$oldName = $user->name;
			$user->name = $suppliedName;
			LogHelper::log('{user} renamed {old} to {new}', [
				'old' => TextHelper::reprUser($oldName),
				'new' => TextHelper::reprUser($suppliedName)]);
		}

		if ($suppliedPassword1 != '')
		{
			Access::assert(
				Privilege::ChangeUserPassword,
				Access::getIdentity($user));

			if ($suppliedPassword1 != $suppliedPassword2)
				throw new SimpleException('Specified passwords must be the same');
			$suppliedPassword = UserModel::validatePassword($suppliedPassword1);
			$user->passHash = UserModel::hashPassword($suppliedPassword, $user->passSalt);
			LogHelper::log('{user} changed {subject}\'s password', ['subject' => TextHelper::reprUser($user)]);
		}

		if ($suppliedEmail != '' and $suppliedEmail != $user->emailConfirmed)
		{
			Access::assert(
				Privilege::ChangeUserEmail,
				Access::getIdentity($user));

			$suppliedEmail = UserModel::validateEmail($suppliedEmail);
			if (Auth::getCurrentUser()->id == $user->id)
			{
				$user->emailUnconfirmed = $suppliedEmail;
				if (!empty($user->emailUnconfirmed))
					$confirmMail = true;
				LogHelper::log('{user} changed e-mail to {mail}', ['mail' => $suppliedEmail]);
			}
			else
			{
				$user->emailUnconfirmed = null;
				$user->emailConfirmed = $suppliedEmail;
				LogHelper::log('{user} changed {subject}\'s e-mail to {mail}', [
					'subject' => TextHelper::reprUser($user),
					'mail' => $suppliedEmail]);
			}
		}

		if ($suppliedAccessRank != '' and $suppliedAccessRank != $user->accessRank)
		{
			Access::assert(
				Privilege::ChangeUserAccessRank,
				Access::getIdentity($user));

			$suppliedAccessRank = UserModel::validateAccessRank($suppliedAccessRank);
			$user->accessRank = $suppliedAccessRank;
			LogHelper::log('{user} changed {subject}\'s access rank to {rank}', [
				'subject' => TextHelper::reprUser($user),
				'rank' => AccessRank::toString($suppliedAccessRank)]);
		}

		if ($confirmMail)
			self::sendEmailChangeConfirmation($user);

		UserModel::save($user);
		if (Auth::getCurrentUser()->id == $user->id)
			Auth::setCurrentUser($user);

		LogHelper::flush();
		$message = 'Account settings updated!';
		if ($confirmMail)
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

	public function registrationAction()
	{
		$context = getContext();
		$context->handleExceptions = true;

		//check if already logged in
		if (Auth::isLoggedIn())
		{
			\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['StaticPagesController', 'mainPageView']));
			exit;
		}

		$suppliedName = InputHelper::get('name');
		$suppliedPassword1 = InputHelper::get('password1');
		$suppliedPassword2 = InputHelper::get('password2');
		$suppliedEmail = InputHelper::get('email');
		$context->suppliedName = $suppliedName;
		$context->suppliedPassword1 = $suppliedPassword1;
		$context->suppliedPassword2 = $suppliedPassword2;
		$context->suppliedEmail = $suppliedEmail;

		if (!InputHelper::get('submit'))
			return;

		$suppliedName = UserModel::validateUserName($suppliedName);

		if ($suppliedPassword1 != $suppliedPassword2)
			throw new SimpleException('Specified passwords must be the same');
		$suppliedPassword = UserModel::validatePassword($suppliedPassword1);

		$suppliedEmail = UserModel::validateEmail($suppliedEmail);
		if (empty($suppliedEmail) and getConfig()->registration->needEmailForRegistering)
			throw new SimpleException('E-mail address is required - you will be sent confirmation e-mail.');

		//register the user
		$dbUser = UserModel::spawn();
		$dbUser->name = $suppliedName;
		$dbUser->passHash = UserModel::hashPassword($suppliedPassword, $dbUser->passSalt);
		$dbUser->emailUnconfirmed = $suppliedEmail;

		$dbUser->joinDate = time();
		if (UserModel::getCount() == 0)
		{
			//very first user
			$dbUser->accessRank = AccessRank::Admin;
			$dbUser->staffConfirmed = true;
			$dbUser->emailUnconfirmed = null;
			$dbUser->emailConfirmed = $suppliedEmail;
		}
		else
		{
			$dbUser->accessRank = AccessRank::Registered;
			$dbUser->staffConfirmed = false;
			$dbUser->staffConfirmed = null;
		}

		//save the user to db if everything went okay
		UserModel::save($dbUser);

		if (!empty($dbUser->emailUnconfirmed))
			self::sendEmailChangeConfirmation($dbUser);

		$message = 'Congratulations, your account was created.';
		if (!empty($context->mailSent))
		{
			$message .= ' Please wait for activation e-mail.';
			if (getConfig()->registration->staffActivation)
				$message .= ' After this, your registration must be confirmed by staff.';
		}
		elseif (getConfig()->registration->staffActivation)
			$message .= ' Your registration must be now confirmed by staff.';

		LogHelper::log('{subject} just signed up', ['subject' => TextHelper::reprUser($dbUser)]);
		Messenger::message($message);

		if (!getConfig()->registration->needEmailForRegistering and !getConfig()->registration->staffActivation)
		{
			Auth::setCurrentUser($dbUser);
		}
	}

	public function activationAction($token)
	{
		$context = getContext();
		$context->viewName = 'message';
		Assets::setSubTitle('account activation');

		$dbToken = TokenModel::findByToken($token);
		TokenModel::checkValidity($dbToken);

		$dbUser = $dbToken->getUser();
		$dbUser->emailConfirmed = $dbUser->emailUnconfirmed;
		$dbUser->emailUnconfirmed = null;
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

	public function passwordResetAction($token)
	{
		$context = getContext();
		$context->viewName = 'message';
		Assets::setSubTitle('password reset');

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

	public function passwordResetProxyAction()
	{
		$context = getContext();
		$context->viewName = 'user-select';
		Assets::setSubTitle('password reset');

		if (!InputHelper::get('submit'))
			return;

		$name = InputHelper::get('name');
		$user = UserModel::findByNameOrEmail($name);
		if (empty($user->emailConfirmed))
			throw new SimpleException('This user has no e-mail confirmed; password reset cannot proceed');

		self::sendPasswordResetConfirmation($user);
		Messenger::message('E-mail sent. Follow instructions to reset password.');
	}

	public function activationProxyAction()
	{
		$context = getContext();
		$context->viewName = 'user-select';
		Assets::setSubTitle('account activation');

		if (!InputHelper::get('submit'))
			return;

		$name = InputHelper::get('name');
		$user = UserModel::findByNameOrEmail($name);
		if (empty($user->emailUnconfirmed))
		{
			if (!empty($user->emailConfirmed))
				throw new SimpleException('E-mail was already confirmed; activation skipped');
			else
				throw new SimpleException('This user has no e-mail specified; activation cannot proceed');
		}
		self::sendEmailChangeConfirmation($user);
		Messenger::message('Activation e-mail resent.');
	}

	private static function sendTokenizedEmail(
		$user,
		$body,
		$subject,
		$senderName,
		$senderEmail,
		$recipientEmail,
		$linkActionName)
	{
		//prepare unique user token
		$token = TokenModel::spawn();
		$token->setUser($user);
		$token->token = TokenModel::forgeUnusedToken();
		$token->used = false;
		$token->expires = null;
		TokenModel::save($token);

		getContext()->mailSent = true;
		$tokens = [];
		$tokens['host'] = $_SERVER['HTTP_HOST'];
		$tokens['token'] = $token->token; //gosh this code looks so silly
		$tokens['nl'] = PHP_EOL;
		if ($linkActionName !== null)
			$tokens['link'] = \Chibi\Router::linkTo(['UserController', $linkActionName], ['token' => $token->token]);

		$body = wordwrap(TextHelper::replaceTokens($body, $tokens), 70);
		$subject = TextHelper::replaceTokens($subject, $tokens);
		$senderName = TextHelper::replaceTokens($senderName, $tokens);
		$senderEmail = TextHelper::replaceTokens($senderEmail, $tokens);

		if (empty($recipientEmail))
			throw new SimpleException('Destination e-mail address was not found');

		$messageId = $_SERVER['REQUEST_TIME'] . md5($_SERVER['REQUEST_TIME']) . '@' . $_SERVER['HTTP_HOST'];

		$headers = [];
		$headers []= sprintf('MIME-Version: 1.0');
		$headers []= sprintf('Content-Transfer-Encoding: 7bit');
		$headers []= sprintf('Date: %s', date('r', $_SERVER['REQUEST_TIME']));
		$headers []= sprintf('Message-ID: <%s>', $messageId);
		$headers []= sprintf('From: %s <%s>', $senderName, $senderEmail);
		$headers []= sprintf('Reply-To: %s', $senderEmail);
		$headers []= sprintf('Return-Path: %s', $senderEmail);
		$headers []= sprintf('Subject: %s', $subject);
		$headers []= sprintf('Content-Type: text/plain; charset=utf-8', $subject);
		$headers []= sprintf('X-Mailer: PHP/%s', phpversion());
		$headers []= sprintf('X-Originating-IP: %s', $_SERVER['SERVER_ADDR']);
		$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		mail($recipientEmail, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $senderEmail);

		LogHelper::log('Sending e-mail with subject "{subject}" to {mail}', [
			'subject' => $subject,
			'mail' => $recipientEmail]);
	}

	private static function sendEmailChangeConfirmation($user)
	{
		$regConfig = getConfig()->registration;
		if (!$regConfig->confirmationEmailEnabled)
		{
			$user->emailConfirmed = $user->emailUnconfirmed;
			$user->emailUnconfirmed = null;
			return;
		}

		return self::sendTokenizedEmail(
			$user,
			$regConfig->confirmationEmailBody,
			$regConfig->confirmationEmailSubject,
			$regConfig->confirmationEmailSenderName,
			$regConfig->confirmationEmailSenderEmail,
			$user->emailUnconfirmed,
			'activationAction');
	}

	private static function sendPasswordResetConfirmation($user)
	{
		$regConfig = getConfig()->registration;

		return self::sendTokenizedEmail(
			$user,
			$regConfig->passwordResetEmailBody,
			$regConfig->passwordResetEmailSubject,
			$regConfig->passwordResetEmailSenderName,
			$regConfig->passwordResetEmailSenderEmail,
			$user->emailConfirmed,
			'passwordResetAction');
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
