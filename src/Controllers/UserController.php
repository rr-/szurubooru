<?php
class UserController
{
	public function listAction($filter, $page)
	{
		$context = getContext();
		Access::assert(
			Privilege::ListUsers);

		$suppliedFilter = $filter ?: InputHelper::get('filter') ?: 'order:alpha,asc';
		$page = max(1, intval($page));
		$usersPerPage = intval(getConfig()->browsing->usersPerPage);

		$users = UserSearchService::getEntities($suppliedFilter, $usersPerPage, $page);
		$userCount = UserSearchService::getEntityCount($suppliedFilter);
		$pageCount = ceil($userCount / $usersPerPage);
		$page = min($pageCount, $page);

		$context->filter = $suppliedFilter;
		$context->transport->users = $users;
		$context->transport->paginator = new StdClass;
		$context->transport->paginator->page = $page;
		$context->transport->paginator->pageCount = $pageCount;
		$context->transport->paginator->entityCount = $userCount;
		$context->transport->paginator->entities = $users;
		$context->transport->paginator->params = func_get_args();
	}

	public function flagAction($name)
	{
		$user = UserModel::findByNameOrEmail($name);
		Access::assert(
			Privilege::FlagUser,
			Access::getIdentity($user));

		if (!InputHelper::get('submit'))
			return;

		$key = TextHelper::reprUser($user);

		$flagged = SessionHelper::get('flagged', []);
		if (in_array($key, $flagged))
			throw new SimpleException('You already flagged this user');
		$flagged []= $key;
		SessionHelper::set('flagged', $flagged);

		LogHelper::log('{user} flagged {subject} for moderator attention', [
			'subject' => TextHelper::reprUser($user)]);

		StatusHelper::success();
	}

	public function banAction($name)
	{
		$user = UserModel::findByNameOrEmail($name);
		Access::assert(
			Privilege::BanUser,
			Access::getIdentity($user));

		if (!InputHelper::get('submit'))
			return;

		$user->banned = true;
		UserModel::save($user);

		LogHelper::log('{user} banned {subject}', ['subject' => TextHelper::reprUser($user)]);
		StatusHelper::success();
	}

	public function unbanAction($name)
	{
		$user = UserModel::findByNameOrEmail($name);
		Access::assert(
			Privilege::BanUser,
			Access::getIdentity($user));

		if (!InputHelper::get('submit'))
			return;

		$user->banned = false;
		UserModel::save($user);

		LogHelper::log('{user} unbanned {subject}', ['subject' => TextHelper::reprUser($user)]);
		StatusHelper::success();
	}

	public function acceptRegistrationAction($name)
	{
		$user = UserModel::findByNameOrEmail($name);
		Access::assert(
			Privilege::AcceptUserRegistration);

		if (!InputHelper::get('submit'))
			return;

		$user->staffConfirmed = true;
		UserModel::save($user);
		LogHelper::log('{user} confirmed {subject}\'s account', ['subject' => TextHelper::reprUser($user)]);
		StatusHelper::success();
	}

	public function deleteAction($name)
	{
		$context = getContext();
		$user = UserModel::findByNameOrEmail($name);
		Access::assert(
			Privilege::ViewUser,
			Access::getIdentity($user));
		Access::assert(
			Privilege::DeleteUser,
			Access::getIdentity($user));

		$this->loadUserView($user);
		$context->transport->tab = 'delete';

		$context->suppliedCurrentPassword = $suppliedCurrentPassword = InputHelper::get('current-password');

		if (!InputHelper::get('submit'))
			return;

		$name = $user->name;
		if (Auth::getCurrentUser()->id == $user->id)
		{
			$suppliedPasswordHash = UserModel::hashPassword($suppliedCurrentPassword, $user->passSalt);
			if ($suppliedPasswordHash != $user->passHash)
				throw new SimpleException('Must supply valid password');
		}

		$oldId = $user->id;
		UserModel::remove($user);
		if ($oldId == Auth::getCurrentUser()->id)
			Auth::logOut();

		\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['IndexController', 'indexAction']));
		LogHelper::log('{user} removed {subject}\'s account', ['subject' => TextHelper::reprUser($name)]);
		StatusHelper::success();
	}

	public function settingsAction($name)
	{
		$context = getContext();
		$user = UserModel::findByNameOrEmail($name);
		Access::assert(
			Privilege::ViewUser,
			Access::getIdentity($user));
		Access::assert(
			Privilege::ChangeUserSettings,
			Access::getIdentity($user));

		$this->loadUserView($user);
		$context->transport->tab = 'settings';

		if (!InputHelper::get('submit'))
			return;

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
		StatusHelper::success('Browsing settings updated!');
	}

	public function editAction($name)
	{
		$context = getContext();
		try
		{
			$user = UserModel::findByNameOrEmail($name);
			Access::assert(
				Privilege::ViewUser,
				Access::getIdentity($user));

			$this->loadUserView($user);
			$context->transport->tab = 'edit';

			$context->suppliedCurrentPassword = $suppliedCurrentPassword = InputHelper::get('current-password');
			$context->suppliedName = $suppliedName = InputHelper::get('name');
			$context->suppliedPassword1 = $suppliedPassword1 = InputHelper::get('password1');
			$context->suppliedPassword2 = $suppliedPassword2 = InputHelper::get('password2');
			$context->suppliedEmail = $suppliedEmail = InputHelper::get('email');
			$context->suppliedAccessRank = $suppliedAccessRank = InputHelper::get('access-rank');
			$currentPasswordHash = $user->passHash;

			if (!InputHelper::get('submit'))
				return;

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

			if (Auth::getCurrentUser()->id == $user->id)
			{
				$suppliedPasswordHash = UserModel::hashPassword($suppliedCurrentPassword, $user->passSalt);
				if ($suppliedPasswordHash != $currentPasswordHash)
					throw new SimpleException('Must supply valid current password');
			}
			UserModel::save($user);
			if (Auth::getCurrentUser()->id == $user->id)
				Auth::setCurrentUser($user);

			if ($confirmMail)
				self::sendEmailChangeConfirmation($user);

			LogHelper::flush();
			$message = 'Account settings updated!';
			if ($confirmMail)
				$message .= ' You will be sent an e-mail address confirmation message soon.';
			StatusHelper::success($message);
		}
		catch (Exception $e)
		{
			$context->transport->user = UserModel::findByNameOrEmail($name);
			throw $e;
		}
	}

	public function viewAction($name, $tab = 'favs', $page)
	{
		$context = getContext();
		$postsPerPage = intval(getConfig()->browsing->postsPerPage);
		$user = UserModel::findByNameOrEmail($name);
		if ($tab === null)
			$tab = 'favs';
		if ($page === null)
			$page = 1;

		Access::assert(
			Privilege::ViewUser,
			Access::getIdentity($user));

		$this->loadUserView($user);

		$query = '';
		if ($tab == 'uploads')
			$query = 'submit:' . $user->name;
		elseif ($tab == 'favs')
			$query = 'fav:' . $user->name;
		else
			throw new SimpleException('Wrong tab');

		$page = max(1, $page);
		$posts = PostSearchService::getEntities($query, $postsPerPage, $page);
		$postCount = PostSearchService::getEntityCount($query, $postsPerPage, $page);
		$pageCount = ceil($postCount / $postsPerPage);
		PostModel::preloadTags($posts);

		$context->transport->tab = $tab;
		$context->transport->lastSearchQuery = $query;
		$context->transport->paginator = new StdClass;
		$context->transport->paginator->page = $page;
		$context->transport->paginator->pageCount = $pageCount;
		$context->transport->paginator->entityCount = $postCount;
		$context->transport->paginator->entities = $posts;
		$context->transport->posts = $posts;
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

		StatusHelper::success();
	}

	public function registrationAction()
	{
		$context = getContext();
		$context->handleExceptions = true;

		//check if already logged in
		if (Auth::isLoggedIn())
		{
			\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['IndexController', 'indexAction']));
			return;
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
		StatusHelper::success($message);

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
		StatusHelper::success($message);

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
		StatusHelper::success($message);

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
		StatusHelper::success('E-mail sent. Follow instructions to reset password.');
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
		StatusHelper::success('Activation e-mail resent.');
	}

	private function loadUserView($user)
	{
		$context = getContext();
		$flagged = in_array(TextHelper::reprUser($user), SessionHelper::get('flagged', []));
		$context->flagged = $flagged;
		$context->transport->user = $user;
		$context->handleExceptions = true;
		$context->viewName = 'user-view';
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
}
