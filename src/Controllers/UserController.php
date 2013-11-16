<?php
class UserController
{
	private static function sendTokenizedEmail(
		$user,
		$body,
		$subject,
		$senderName,
		$senderEmail,
		$recipientEmail,
		$tokens)
	{
		//prepare unique user token
		do
		{
			$tokenText =  md5(mt_rand() . uniqid());
		}
		while (R::findOne('usertoken', 'token = ?', [$tokenText]) !== null);
		$token = R::dispense('usertoken');
		$token->user = $user;
		$token->token = $tokenText;
		$token->used = false;
		$token->expires = null;
		R::store($token);

		\Chibi\Registry::getContext()->mailSent = true;
		$tokens['host'] = $_SERVER['HTTP_HOST'];
		$tokens['token'] = $tokenText;

		$body = wordwrap(TextHelper::replaceTokens($body, $tokens), 70);
		$subject = TextHelper::replaceTokens($subject, $tokens);
		$senderName = TextHelper::replaceTokens($senderName, $tokens);
		$senderEmail = TextHelper::replaceTokens($senderEmail, $tokens);

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
		$subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		mail($recipientEmail, $subject, $body, implode("\r\n", $headers), '-f' . $senderEmail);
	}

	private static function sendEmailChangeConfirmation($user)
	{
		$regConfig = \Chibi\Registry::getConfig()->registration;
		if (!$regConfig->confirmationEmailEnabled)
		{
			$user->email_confirmed = $user->email_unconfirmed;
			$user->email_unconfirmed = null;
			return;
		}

		$tokens = [];
		$tokens['link'] = \Chibi\UrlHelper::route('user', 'activation', ['token' => '{token}']);

		return self::sendTokenizedEmail(
			$user,
			$regConfig->confirmationEmailBody,
			$regConfig->confirmationEmailSubject,
			$regConfig->confirmationEmailSenderName,
			$regConfig->confirmationEmailSenderEmail,
			$user->email_unconfirmed,
			$tokens);
	}



	/**
	* @route /users
	* @route /users/{page}
	* @route /users/{sortStyle}
	* @route /users/{sortStyle}/{page}
	* @validate sortStyle alpha|alpha,asc|alpha,desc|date,asc|date,desc|pending
	* @validate page [0-9]+
	*/
	public function listAction($sortStyle, $page)
	{
		$this->context->stylesheets []= 'user-list.css';
		$this->context->stylesheets []= 'paginator.css';
		if ($this->context->user->hasEnabledEndlessScrolling())
			$this->context->scripts []= 'paginator-endless.js';

		if ($sortStyle == '' or $sortStyle == 'alpha')
			$sortStyle = 'alpha,asc';
		if ($sortStyle == 'date')
			$sortStyle = 'date,asc';

		$page = intval($page);
		$usersPerPage = intval($this->config->browsing->usersPerPage);
		$this->context->subTitle = 'users';
		PrivilegesHelper::confirmWithException(Privilege::ListUsers);

		$userCount = Model_User::getEntityCount($sortStyle);
		$pageCount = ceil($userCount / $usersPerPage);
		$page = max(1, min($pageCount, $page));
		$users = Model_User::getEntities($sortStyle, $usersPerPage, $page);

		$this->context->sortStyle = $sortStyle;
		$this->context->transport->paginator = new StdClass;
		$this->context->transport->paginator->page = $page;
		$this->context->transport->paginator->pageCount = $pageCount;
		$this->context->transport->paginator->entityCount = $userCount;
		$this->context->transport->paginator->entities = $users;
		$this->context->transport->paginator->params = func_get_args();
		$this->context->transport->users = $users;
	}



	/**
	* @route /user/{name}/ban
	* @validate name [^\/]+
	*/
	public function banAction($name)
	{
		$user = Model_User::locate($name);
		PrivilegesHelper::confirmWithException(Privilege::BanUser, PrivilegesHelper::getIdentitySubPrivilege($user));
		if (InputHelper::get('submit'))
		{
			$user->banned = true;
			R::store($user);
			StatusHelper::success();
		}
	}

	/**
	* @route /post/{name}/unban
	* @validate name [^\/]+
	*/
	public function unbanAction($name)
	{
		$user = Model_User::locate($name);
		PrivilegesHelper::confirmWithException(Privilege::BanUser, PrivilegesHelper::getIdentitySubPrivilege($user));
		if (InputHelper::get('submit'))
		{
			$user->banned = false;
			R::store($user);
			StatusHelper::success();
		}
	}

	/**
	* @route /post/{name}/accept-registration
	* @validate name [^\/]+
	*/
	public function acceptRegistrationAction($name)
	{
		$user = Model_User::locate($name);
		PrivilegesHelper::confirmWithException(Privilege::AcceptUserRegistration);
		if (InputHelper::get('submit'))
		{
			$user->staff_confirmed = true;
			R::store($user);
			StatusHelper::success();
		}
	}



	/**
	* @route /user/{name}/delete
	* @validate name [^\/]+
	*/
	public function deleteAction($name)
	{
		$user = Model_User::locate($name);
		PrivilegesHelper::confirmWithException(Privilege::ViewUser, PrivilegesHelper::getIdentitySubPrivilege($user));
		PrivilegesHelper::confirmWithException(Privilege::DeleteUser, PrivilegesHelper::getIdentitySubPrivilege($user));

		$this->context->handleExceptions = true;
		$this->context->transport->user = $user;
		$this->context->transport->tab = 'delete';
		$this->context->viewName = 'user-view';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->stylesheets []= 'user-view.css';
		$this->context->subTitle = $name;

		$this->context->suppliedCurrentPassword = $suppliedCurrentPassword = InputHelper::get('current-password');

		if (InputHelper::get('submit'))
		{
			if ($this->context->user->id == $user->id)
			{
				$suppliedPasswordHash = Model_User::hashPassword($suppliedCurrentPassword, $user->pass_salt);
				if ($suppliedPasswordHash != $user->pass_hash)
					throw new SimpleException('Must supply valid password');
			}
			foreach ($user->alias('commenter')->ownComment as $comment)
			{
				$comment->commenter = null;
				R::store($comment);
			}
			foreach ($user->alias('uploader')->ownPost as $post)
			{
				$post->uploader = null;
				R::store($post);
			}
			$user->ownFavoritee = [];
			if ($user->id == $this->context->user->id)
				AuthController::doLogOut();
			R::store($user);
			R::trash($user);
			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
			StatusHelper::success();
		}
	}



	/**
	* @route /user/{name}/settings
	* @validate name [^\/]+
	*/
	public function settingsAction($name)
	{
		$user = Model_User::locate($name);
		PrivilegesHelper::confirmWithException(Privilege::ViewUser, PrivilegesHelper::getIdentitySubPrivilege($user));
		PrivilegesHelper::confirmWithException(Privilege::ChangeUserSettings, PrivilegesHelper::getIdentitySubPrivilege($user));

		$this->context->handleExceptions = true;
		$this->context->transport->user = $user;
		$this->context->transport->tab = 'settings';
		$this->context->viewName = 'user-view';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->stylesheets []= 'user-view.css';
		$this->context->subTitle = $name;

		if (InputHelper::get('submit'))
		{
			$suppliedSafety = InputHelper::get('safety');
			if (!is_array($suppliedSafety))
				$suppliedSafety = [];
			foreach (PostSafety::getAll() as $safety)
				$user->enableSafety($safety, in_array($safety, $suppliedSafety));

			$user->enableEndlessScrolling(InputHelper::get('endless-scrolling'));

			R::store($user);
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
			$user = Model_User::locate($name);
			PrivilegesHelper::confirmWithException(Privilege::ViewUser, PrivilegesHelper::getIdentitySubPrivilege($user));

			$this->context->handleExceptions = true;
			$this->context->transport->user = $user;
			$this->context->transport->tab = 'edit';
			$this->context->viewName = 'user-view';
			$this->context->stylesheets []= 'tabs.css';
			$this->context->stylesheets []= 'user-view.css';
			$this->context->subTitle = $name;

			$this->context->suppliedCurrentPassword = $suppliedCurrentPassword = InputHelper::get('current-password');
			$this->context->suppliedName = $suppliedName = InputHelper::get('name');
			$this->context->suppliedPassword1 = $suppliedPassword1 = InputHelper::get('password1');
			$this->context->suppliedPassword2 = $suppliedPassword2 = InputHelper::get('password2');
			$this->context->suppliedEmail = $suppliedEmail = InputHelper::get('email');
			$this->context->suppliedAccessRank = $suppliedAccessRank = InputHelper::get('access-rank');
			$currentPasswordHash = $user->pass_hash;

			if (InputHelper::get('submit'))
			{
				$confirmMail = false;

				if ($suppliedName != '' and $suppliedName != $user->name)
				{
					PrivilegesHelper::confirmWithException(Privilege::ChangeUserName, PrivilegesHelper::getIdentitySubPrivilege($user));
					$suppliedName = Model_User::validateUserName($suppliedName);
					$user->name = $suppliedName;
				}

				if ($suppliedPassword1 != '')
				{
					PrivilegesHelper::confirmWithException(Privilege::ChangeUserPassword, PrivilegesHelper::getIdentitySubPrivilege($user));
					if ($suppliedPassword1 != $suppliedPassword2)
						throw new SimpleException('Specified passwords must be the same');
					$suppliedPassword = Model_User::validatePassword($suppliedPassword1);
					$user->pass_hash = Model_User::hashPassword($suppliedPassword, $user->pass_salt);
				}

				if ($suppliedEmail != '' and $suppliedEmail != $user->email_confirmed)
				{
					PrivilegesHelper::confirmWithException(Privilege::ChangeUserEmail, PrivilegesHelper::getIdentitySubPrivilege($user));
					$suppliedEmail = Model_User::validateEmail($suppliedEmail);
					if ($this->context->user->id == $user->id)
					{
						$user->email_unconfirmed = $suppliedEmail;
						if (!empty($user->email_unconfirmed))
							$confirmMail = true;
					}
					else
					{
						$user->email_unconfirmed = null;
						$user->email_confirmed = $suppliedEmail;
					}
				}

				if ($suppliedAccessRank != '' and $suppliedAccessRank != $user->access_rank)
				{
					PrivilegesHelper::confirmWithException(Privilege::ChangeUserAccessRank, PrivilegesHelper::getIdentitySubPrivilege($user));
					$suppliedAccessRank = Model_User::validateAccessRank($suppliedAccessRank);
					$user->access_rank = $suppliedAccessRank;
				}

				if ($this->context->user->id == $user->id)
				{
					$suppliedPasswordHash = Model_User::hashPassword($suppliedCurrentPassword, $user->pass_salt);
					if ($suppliedPasswordHash != $currentPasswordHash)
						throw new SimpleException('Must supply valid current password');
				}
				R::store($user);

				if ($confirmMail)
					self::sendEmailChangeConfirmation($user);

				$message = 'Account settings updated!';
				if ($confirmMail)
					$message .= ' You wlil be sent an e-mail address confirmation message soon.';
				StatusHelper::success($message);
			}
		}
		catch (Exception $e)
		{
			$this->context->transport->user = Model_User::locate($name);
			throw $e;
		}
	}



	/**
	* @route /user/{name}
	* @route /user/{name}/{tab}/{page}
	* @validate name [^\/]+
	* @validate tab favs|uploads
	* @validate page \d*
	*/
	public function viewAction($name, $tab, $page)
	{
		$postsPerPage = intval($this->config->browsing->postsPerPage);
		$user = Model_User::locate($name);
		if ($tab === null)
			$tab = 'favs';
		if ($page === null)
			$page = 1;

		PrivilegesHelper::confirmWithException(Privilege::ViewUser, PrivilegesHelper::getIdentitySubPrivilege($user));
		$this->context->stylesheets []= 'tabs.css';
		$this->context->stylesheets []= 'user-view.css';
		$this->context->stylesheets []= 'post-list.css';
		$this->context->stylesheets []= 'post-small.css';
		$this->context->stylesheets []= 'paginator.css';
		if ($this->context->user->hasEnabledEndlessScrolling())
			$this->context->scripts []= 'paginator-endless.js';
		$this->context->subTitle = $name;

		$query = '';
		if ($tab == 'uploads')
			$query = 'submit:' . $user->name;
		elseif ($tab == 'favs')
			$query = 'fav:' . $user->name;
		else
			throw new SimpleException('Wrong tab');

		$postCount = Model_Post::getEntityCount($query);
		$pageCount = ceil($postCount / $postsPerPage);
		$page = max(1, min($pageCount, $page));
		$posts = Model_Post::getEntities($query, $postsPerPage, $page);

		$this->context->transport->user = $user;
		$this->context->transport->tab = $tab;
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

		AuthController::doReLog();
		if (!$this->context->user->anonymous)
			R::store($this->context->user);

		StatusHelper::success();
	}



	/**
	* @route /register
	*/
	public function registrationAction()
	{
		$this->context->handleExceptions = true;
		$this->context->stylesheets []= 'auth.css';
		$this->context->subTitle = 'registration form';

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
			$suppliedName = Model_User::validateUserName($suppliedName);

			if ($suppliedPassword1 != $suppliedPassword2)
				throw new SimpleException('Specified passwords must be the same');
			$suppliedPassword = Model_User::validatePassword($suppliedPassword1);

			$suppliedEmail = Model_User::validateEmail($suppliedEmail);
			if (empty($suppliedEmail) and $this->config->registration->needEmailForRegistering)
				throw new SimpleException('E-mail address is required - you will be sent confirmation e-mail.');

			//register the user
			$dbUser = R::dispense('user');
			$dbUser->name = $suppliedName;
			$dbUser->pass_salt = md5(mt_rand() . uniqid());
			$dbUser->pass_hash = Model_User::hashPassword($suppliedPassword, $dbUser->pass_salt);
			$dbUser->email_unconfirmed = $suppliedEmail;

			$dbUser->join_date = time();
			if (R::findOne('user') === null)
			{
				//very first user
				$dbUser->access_rank = AccessRank::Admin;
				$dbUser->staff_confirmed = true;
				$dbUser->email_unconfirmed = null;
				$dbUser->email_confirmed = $suppliedEmail;
			}
			else
			{
				$dbUser->access_rank = AccessRank::Registered;
				$dbUser->staff_confirmed = false;
				$dbUser->staff_confirmed = null;
			}

			//save the user to db if everything went okay
			R::store($dbUser);

			if (!empty($dbUser->email_unconfirmed))
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
		$this->context->subTitle = 'account activation';
		$this->context->viewName = 'message';

		if (empty($token))
			throw new SimpleException('Invalid activation token');

		$dbToken = R::findOne('usertoken', 'token = ?', [$token]);
		if ($dbToken === null)
			throw new SimpleException('No user with such activation token');

		if ($dbToken->used)
			throw new SimpleException('This user was already activated');

		if ($dbToken->expires !== null and time() > $dbToken->expires)
			throw new SimpleException('Activation link expired');

		$dbUser = $dbToken->user;
		$dbUser->email_confirmed = $dbUser->email_unconfirmed;
		$dbUser->email_unconfirmed = null;
		$dbToken->used = true;
		R::store($dbToken);
		R::store($dbUser);

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
}
