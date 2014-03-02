<?php
class UserController
{
	private function loadUserView($user)
	{
		$flagged = in_array(TextHelper::reprUser($user), SessionHelper::get('flagged', []));
		$this->context->flagged = $flagged;
		$this->context->transport->user = $user;
		$this->context->handleExceptions = true;
		$this->context->viewName = 'user-view';
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

		\Chibi\Registry::getContext()->mailSent = true;
		$tokens = [];
		$tokens['host'] = $_SERVER['HTTP_HOST'];
		$tokens['token'] = $token->token; //gosh this code looks so silly
		$tokens['nl'] = PHP_EOL;
		if ($linkActionName !== null)
			$tokens['link'] = \Chibi\UrlHelper::route('user', $linkActionName, ['token' => $token->token]);

		$body = wordwrap(TextHelper::replaceTokens($body, $tokens), 70);
		$subject = TextHelper::replaceTokens($subject, $tokens);
		$senderName = TextHelper::replaceTokens($senderName, $tokens);
		$senderEmail = TextHelper::replaceTokens($senderEmail, $tokens);

		if (empty($recipientEmail))
			throw new SimpleException('Destination e-mail address was not found');

		$headers = [];
		$headers []= sprintf('MIME-Version: 1.0');
		$headers []= sprintf('Content-Transfer-Encoding: 7bit');
		$headers []= sprintf('Date: %s', date('r', $_SERVER['REQUEST_TIME']));
		$headers []= sprintf('Message-ID: <%s>', $_SERVER['REQUEST_TIME'] . md5($_SERVER['REQUEST_TIME']) . '@' . $_SERVER['HTTP_HOST']);
		$headers []= sprintf('From: %s <%s>', $senderName, $senderEmail);
		$headers []= sprintf('Reply-To: %s', $senderEmail);
		$headers []= sprintf('Return-Path: %s', $senderEmail);
		$headers []= sprintf('Subject: %s', $subject);
		$headers []= sprintf('Content-Type: text/plain; charset=utf-8', $subject);
		$headers []= sprintf('X-Mailer: PHP/%s', phpversion());
		$headers []= sprintf('X-Originating-IP: %s', $_SERVER['SERVER_ADDR']);
		$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		mail($recipientEmail, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $senderEmail);

		LogHelper::log('Sending e-mail with subject "{subject}" to {mail}', ['subject' => $subject, 'mail' => $recipientEmail]);
	}

	private static function sendEmailChangeConfirmation($user)
	{
		$regConfig = \Chibi\Registry::getConfig()->registration;
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
			'activation');
	}

	private static function sendPasswordResetConfirmation($user)
	{
		$regConfig = \Chibi\Registry::getConfig()->registration;

		return self::sendTokenizedEmail(
			$user,
			$regConfig->passwordResetEmailBody,
			$regConfig->passwordResetEmailSubject,
			$regConfig->passwordResetEmailSenderName,
			$regConfig->passwordResetEmailSenderEmail,
			$user->emailConfirmed,
			'password-reset');
	}



	/**
	* @route /users
	* @route /users/{page}
	* @route /users/{filter}
	* @route /users/{filter}/{page}
	* @validate filter [a-zA-Z\32:,_-]+
	* @validate page [0-9]+
	*/
	public function listAction($filter, $page)
	{
		PrivilegesHelper::confirmWithException(Privilege::ListUsers);

		$suppliedFilter = $filter ?: InputHelper::get('filter') ?: 'order:alpha,asc';
		$page = max(1, intval($page));
		$usersPerPage = intval($this->config->browsing->usersPerPage);

		$users = UserSearchService::getEntities($suppliedFilter, $usersPerPage, $page);
		$userCount = UserSearchService::getEntityCount($suppliedFilter);
		$pageCount = ceil($userCount / $usersPerPage);
		$page = min($pageCount, $page);

		$this->context->filter = $suppliedFilter;
		$this->context->transport->users = $users;
		$this->context->transport->paginator = new StdClass;
		$this->context->transport->paginator->page = $page;
		$this->context->transport->paginator->pageCount = $pageCount;
		$this->context->transport->paginator->entityCount = $userCount;
		$this->context->transport->paginator->entities = $users;
		$this->context->transport->paginator->params = func_get_args();
	}



	/**
	* @route /user/{name}/flag
	* @validate name [^\/]+
	*/
	public function flagAction($name)
	{
		$user = UserModel::findByNameOrEmail($name);
		PrivilegesHelper::confirmWithException(Privilege::FlagUser, PrivilegesHelper::getIdentitySubPrivilege($user));

		if (InputHelper::get('submit'))
		{
			$key = TextHelper::reprUser($user);

			$flagged = SessionHelper::get('flagged', []);
			if (in_array($key, $flagged))
				throw new SimpleException('You already flagged this user');
			$flagged []= $key;
			SessionHelper::set('flagged', $flagged);

			LogHelper::log('{user} flagged {subject} for moderator attention', ['subject' => TextHelper::reprUser($user)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /user/{name}/ban
	* @validate name [^\/]+
	*/
	public function banAction($name)
	{
		$user = UserModel::findByNameOrEmail($name);
		PrivilegesHelper::confirmWithException(Privilege::BanUser, PrivilegesHelper::getIdentitySubPrivilege($user));

		if (InputHelper::get('submit'))
		{
			$user->banned = true;
			UserModel::save($user);

			LogHelper::log('{user} banned {subject}', ['subject' => TextHelper::reprUser($user)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{name}/unban
	* @validate name [^\/]+
	*/
	public function unbanAction($name)
	{
		$user = UserModel::findByNameOrEmail($name);
		PrivilegesHelper::confirmWithException(Privilege::BanUser, PrivilegesHelper::getIdentitySubPrivilege($user));

		if (InputHelper::get('submit'))
		{
			$user->banned = false;
			UserModel::save($user);

			LogHelper::log('{user} unbanned {subject}', ['subject' => TextHelper::reprUser($user)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{name}/accept-registration
	* @validate name [^\/]+
	*/
	public function acceptRegistrationAction($name)
	{
		$user = UserModel::findByNameOrEmail($name);
		PrivilegesHelper::confirmWithException(Privilege::AcceptUserRegistration);
		if (InputHelper::get('submit'))
		{
			$user->staffConfirmed = true;
			UserModel::save($user);
			LogHelper::log('{user} confirmed {subject}\'s account', ['subject' => TextHelper::reprUser($user)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /user/{name}/delete
	* @validate name [^\/]+
	*/
	public function deleteAction($name)
	{
		$user = UserModel::findByNameOrEmail($name);
		PrivilegesHelper::confirmWithException(Privilege::ViewUser, PrivilegesHelper::getIdentitySubPrivilege($user));
		PrivilegesHelper::confirmWithException(Privilege::DeleteUser, PrivilegesHelper::getIdentitySubPrivilege($user));

		$this->loadUserView($user);
		$this->context->transport->tab = 'delete';

		$this->context->suppliedCurrentPassword = $suppliedCurrentPassword = InputHelper::get('current-password');

		if (InputHelper::get('submit'))
		{
			$name = $user->name;
			if ($this->context->user->id == $user->id)
			{
				$suppliedPasswordHash = UserModel::hashPassword($suppliedCurrentPassword, $user->passSalt);
				if ($suppliedPasswordHash != $user->passHash)
					throw new SimpleException('Must supply valid password');
			}

			$oldId = $user->id;
			UserModel::remove($user);
			if ($oldId == $this->context->user->id)
				AuthController::doLogOut();

			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
			LogHelper::log('{user} removed {subject}\'s account', ['subject' => TextHelper::reprUser($name)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /user/{name}/settings
	* @validate name [^\/]+
	*/
	public function settingsAction($name)
	{
		$user = UserModel::findByNameOrEmail($name);
		PrivilegesHelper::confirmWithException(Privilege::ViewUser, PrivilegesHelper::getIdentitySubPrivilege($user));
		PrivilegesHelper::confirmWithException(Privilege::ChangeUserSettings, PrivilegesHelper::getIdentitySubPrivilege($user));

		$this->loadUserView($user);
		$this->context->transport->tab = 'settings';

		if (InputHelper::get('submit'))
		{
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
			if ($user->id == $this->context->user->id)
				$this->context->user = $user;
			AuthController::doReLog();
			StatusHelper::success('Browsing settings updated!');
		}
	}



	/**
	* @route /user/{name}/edit
	* @validate name [^\/]+
	*/
	public function editAction($name)
	{
		try
		{
			$user = UserModel::findByNameOrEmail($name);
			PrivilegesHelper::confirmWithException(Privilege::ViewUser, PrivilegesHelper::getIdentitySubPrivilege($user));

			$this->loadUserView($user);
			$this->context->transport->tab = 'edit';

			$this->context->suppliedCurrentPassword = $suppliedCurrentPassword = InputHelper::get('current-password');
			$this->context->suppliedName = $suppliedName = InputHelper::get('name');
			$this->context->suppliedPassword1 = $suppliedPassword1 = InputHelper::get('password1');
			$this->context->suppliedPassword2 = $suppliedPassword2 = InputHelper::get('password2');
			$this->context->suppliedEmail = $suppliedEmail = InputHelper::get('email');
			$this->context->suppliedAccessRank = $suppliedAccessRank = InputHelper::get('access-rank');
			$currentPasswordHash = $user->passHash;

			if (InputHelper::get('submit'))
			{
				$confirmMail = false;
				LogHelper::bufferChanges();

				if ($suppliedName != '' and $suppliedName != $user->name)
				{
					PrivilegesHelper::confirmWithException(Privilege::ChangeUserName, PrivilegesHelper::getIdentitySubPrivilege($user));
					$suppliedName = UserModel::validateUserName($suppliedName);
					$oldName = $user->name;
					$user->name = $suppliedName;
					LogHelper::log('{user} renamed {old} to {new}', ['old' => TextHelper::reprUser($oldName), 'new' => TextHelper::reprUser($suppliedName)]);
				}

				if ($suppliedPassword1 != '')
				{
					PrivilegesHelper::confirmWithException(Privilege::ChangeUserPassword, PrivilegesHelper::getIdentitySubPrivilege($user));
					if ($suppliedPassword1 != $suppliedPassword2)
						throw new SimpleException('Specified passwords must be the same');
					$suppliedPassword = UserModel::validatePassword($suppliedPassword1);
					$user->passHash = UserModel::hashPassword($suppliedPassword, $user->passSalt);
					LogHelper::log('{user} changed {subject}\'s password', ['subject' => TextHelper::reprUser($user)]);
				}

				if ($suppliedEmail != '' and $suppliedEmail != $user->emailConfirmed)
				{
					PrivilegesHelper::confirmWithException(Privilege::ChangeUserEmail, PrivilegesHelper::getIdentitySubPrivilege($user));
					$suppliedEmail = UserModel::validateEmail($suppliedEmail);
					if ($this->context->user->id == $user->id)
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
						LogHelper::log('{user} changed {subject}\'s e-mail to {mail}', ['subject' => TextHelper::reprUser($user), 'mail' => $suppliedEmail]);
					}
				}

				if ($suppliedAccessRank != '' and $suppliedAccessRank != $user->accessRank)
				{
					PrivilegesHelper::confirmWithException(Privilege::ChangeUserAccessRank, PrivilegesHelper::getIdentitySubPrivilege($user));
					$suppliedAccessRank = UserModel::validateAccessRank($suppliedAccessRank);
					$user->accessRank = $suppliedAccessRank;
					LogHelper::log('{user} changed {subject}\'s access rank to {rank}', ['subject' => TextHelper::reprUser($user), 'rank' => AccessRank::toString($suppliedAccessRank)]);
				}

				if ($this->context->user->id == $user->id)
				{
					$suppliedPasswordHash = UserModel::hashPassword($suppliedCurrentPassword, $user->passSalt);
					if ($suppliedPasswordHash != $currentPasswordHash)
						throw new SimpleException('Must supply valid current password');
				}
				UserModel::save($user);
				if ($this->context->user->id == $user->id)
					AuthController::doReLog();

				if ($confirmMail)
					self::sendEmailChangeConfirmation($user);

				LogHelper::flush();
				$message = 'Account settings updated!';
				if ($confirmMail)
					$message .= ' You will be sent an e-mail address confirmation message soon.';
				StatusHelper::success($message);
			}
		}
		catch (Exception $e)
		{
			$this->context->transport->user = UserModel::findByNameOrEmail($name);
			throw $e;
		}
	}



	/**
	* @route /user/{name}/{tab}
	* @route /user/{name}/{tab}/{page}
	* @validate name [^\/]+
	* @validate tab favs|uploads
	* @validate page \d*
	*/
	public function viewAction($name, $tab = 'favs', $page)
	{
		$postsPerPage = intval($this->config->browsing->postsPerPage);
		$user = UserModel::findByNameOrEmail($name);
		if ($tab === null)
			$tab = 'favs';
		if ($page === null)
			$page = 1;

		PrivilegesHelper::confirmWithException(Privilege::ViewUser, PrivilegesHelper::getIdentitySubPrivilege($user));
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

		$this->context->transport->tab = $tab;
		$this->context->transport->lastSearchQuery = $query;
		$this->context->transport->paginator = new StdClass;
		$this->context->transport->paginator->page = $page;
		$this->context->transport->paginator->pageCount = $pageCount;
		$this->context->transport->paginator->entityCount = $postCount;
		$this->context->transport->paginator->entities = $posts;
		$this->context->transport->posts = $posts;
	}



	/**
	* @route /user/toggle-safety/{safety}
	*/
	public function toggleSafetyAction($safety)
	{
		PrivilegesHelper::confirmWithException(Privilege::ChangeUserSettings, PrivilegesHelper::getIdentitySubPrivilege($this->context->user));

		if (!in_array($safety, PostSafety::getAll()))
			throw new SimpleExcetpion('Invalid safety');

		$this->context->user->enableSafety($safety,
			!$this->context->user->hasEnabledSafety($safety));

		if ($this->context->user->accessRank != AccessRank::Anonymous)
			UserModel::save($this->context->user);
		AuthController::doReLog();

		StatusHelper::success();
	}



	/**
	* @route /register
	*/
	public function registrationAction()
	{
		$this->context->handleExceptions = true;

		//check if already logged in
		if ($this->context->loggedIn)
		{
			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
			return;
		}

		$suppliedName = InputHelper::get('name');
		$suppliedPassword1 = InputHelper::get('password1');
		$suppliedPassword2 = InputHelper::get('password2');
		$suppliedEmail = InputHelper::get('email');
		$this->context->suppliedName = $suppliedName;
		$this->context->suppliedPassword1 = $suppliedPassword1;
		$this->context->suppliedPassword2 = $suppliedPassword2;
		$this->context->suppliedEmail = $suppliedEmail;

		if (InputHelper::get('submit'))
		{
			$suppliedName = UserModel::validateUserName($suppliedName);

			if ($suppliedPassword1 != $suppliedPassword2)
				throw new SimpleException('Specified passwords must be the same');
			$suppliedPassword = UserModel::validatePassword($suppliedPassword1);

			$suppliedEmail = UserModel::validateEmail($suppliedEmail);
			if (empty($suppliedEmail) and $this->config->registration->needEmailForRegistering)
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
			if (!empty($this->context->mailSent))
			{
				$message .= ' Please wait for activation e-mail.';
				if ($this->config->registration->staffActivation)
					$message .= ' After this, your registration must be confirmed by staff.';
			}
			elseif ($this->config->registration->staffActivation)
				$message .= ' Your registration must be now confirmed by staff.';

			LogHelper::log('{subject} just signed up', ['subject' => TextHelper::reprUser($dbUser)]);
			StatusHelper::success($message);

			if (!$this->config->registration->needEmailForRegistering and !$this->config->registration->staffActivation)
			{
				$this->context->user = $dbUser;
				AuthController::doReLog();
			}
		}
	}



	/**
	* @route /activation/{token}
	*/
	public function activationAction($token)
	{
		$this->context->viewName = 'message';
		CustomAssetViewDecorator::setSubTitle('account activation');

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
		if ($this->config->registration->staffActivation)
			$message .= ' However, your account still must be confirmed by staff.';
		StatusHelper::success($message);

		if (!$this->config->registration->staffActivation)
		{
			$this->context->user = $dbUser;
			AuthController::doReLog();
		}
	}



	/**
	* @route /password-reset/{token}
	*/
	public function passwordResetAction($token)
	{
		$this->context->viewName = 'message';
		CustomAssetViewDecorator::setSubTitle('password reset');

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

		$this->context->user = $dbUser;
		AuthController::doReLog();
	}




	/**
	* @route /password-reset-proxy
	*/
	public function passwordResetProxyAction()
	{
		$this->context->viewName = 'user-select';
		CustomAssetViewDecorator::setSubTitle('password reset');

		if (InputHelper::get('submit'))
		{
			$name = InputHelper::get('name');
			$user = UserModel::findByNameOrEmail($name);
			if (empty($user->emailConfirmed))
				throw new SimpleException('This user has no e-mail confirmed; password reset cannot proceed');

			self::sendPasswordResetConfirmation($user);
			StatusHelper::success('E-mail sent. Follow instructions to reset password.');
		}
	}

	/**
	* @route /activation-proxy
	*/
	public function activationProxyAction()
	{
		$this->context->viewName = 'user-select';
		CustomAssetViewDecorator::setSubTitle('account activation');

		if (InputHelper::get('submit'))
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
			self::sendEmailChangeConfirmation($user);
			StatusHelper::success('Activation e-mail resent.');
		}
	}
}
