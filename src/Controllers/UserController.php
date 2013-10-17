<?php
class UserController
{
	private static function sendEmailConfirmation(&$user)
	{
		$regConfig = \Chibi\Registry::getConfig()->registration;

		if (!$regConfig->confirmationEmailEnabled)
		{
			$user->email_confirmed = $user->email_unconfirmed;
			$user->email_unconfirmed = null;
			return;
		}

		\Chibi\Registry::getContext()->mailSent = true;
		$tokens = [];
		$tokens['host'] = $_SERVER['HTTP_HOST'];
		$tokens['link'] = \Chibi\UrlHelper::route('user', 'activation', ['token' => $user->email_token]);

		$body = wordwrap(TextHelper::replaceTokens($regConfig->confirmationEmailBody, $tokens), 70);
		$subject = TextHelper::replaceTokens($regConfig->confirmationEmailSubject, $tokens);
		$senderName = TextHelper::replaceTokens($regConfig->confirmationEmailSenderName, $tokens);
		$senderEmail = TextHelper::replaceTokens($regConfig->confirmationEmailSenderEmail, $tokens);
		$recipientEmail = $user->email_unconfirmed;

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
		if ($this->config->browsing->endlessScrolling)
			$this->context->scripts []= 'paginator-endless.js';

		$page = intval($page);
		$usersPerPage = intval($this->config->browsing->usersPerPage);
		$this->context->subTitle = 'browsing users';
		PrivilegesHelper::confirmWithException(Privilege::ListUsers);

		if ($sortStyle == '' or $sortStyle == 'alpha')
			$sortStyle = 'alpha,asc';
		if ($sortStyle == 'date')
			$sortStyle = 'date,asc';

		$buildDbQuery = function($dbQuery, $sortStyle)
		{
			$dbQuery->from('user');

			switch ($sortStyle)
			{
				case 'alpha,asc':
					$dbQuery->orderBy('name')->asc();
					break;
				case 'alpha,desc':
					$dbQuery->orderBy('name')->desc();
					break;
				case 'date,asc':
					$dbQuery->orderBy('join_date')->asc();
					break;
				case 'date,desc':
					$dbQuery->orderBy('join_date')->desc();
					break;
				case 'pending':
					$dbQuery->where('staff_confirmed IS NULL');
					$dbQuery->or('staff_confirmed = 0');
					break;
				default:
					throw new SimpleException('Unknown sort style');
			}
		};

		$countDbQuery = R::$f->begin();
		$countDbQuery->select('COUNT(1)')->as('count');
		$buildDbQuery($countDbQuery, $sortStyle);
		$userCount = intval($countDbQuery->get('row')['count']);
		$pageCount = ceil($userCount / $usersPerPage);
		$page = max(1, min($pageCount, $page));

		$searchDbQuery = R::$f->begin();
		$searchDbQuery->select('user.*');
		$buildDbQuery($searchDbQuery, $sortStyle);
		$searchDbQuery->limit('?')->put($usersPerPage);
		$searchDbQuery->offset('?')->put(($page - 1) * $usersPerPage);

		$users = $searchDbQuery->get();
		$users = R::convertToBeans('user', $users);
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
		$user->banned = true;
		R::store($user);
		$this->context->transport->success = true;
	}

	/**
	* @route /post/{name}/unban
	* @validate name [^\/]+
	*/
	public function unbanAction($name)
	{
		$user = Model_User::locate($name);
		PrivilegesHelper::confirmWithException(Privilege::BanUser, PrivilegesHelper::getIdentitySubPrivilege($user));
		$user->banned = false;
		R::store($user);
		$this->context->transport->success = true;
	}

	/**
	* @route /post/{name}/accept-registration
	* @validate name [^\/]+
	*/
	public function acceptRegistrationAction($name)
	{
		$user = Model_User::locate($name);
		PrivilegesHelper::confirmWithException(Privilege::AcceptUserRegistration);
		$user->staff_confirmed = true;
		R::store($user);
		$this->context->transport->success = true;
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
		$this->context->stylesheets []= 'user-view.css';
		$this->context->subTitle = $name;

		$this->context->suppliedCurrentPassword = $suppliedCurrentPassword = InputHelper::get('current-password');

		if (InputHelper::get('remove'))
		{
			if ($this->context->user->id == $user->id)
			{
				$suppliedPasswordHash = Model_User::hashPassword($suppliedCurrentPassword, $user->pass_salt);
				if ($suppliedPasswordHash != $user->pass_hash)
					throw new SimpleException('Must supply valid password');
			}
			$user->ownFavoritee = [];
			R::store($user);
			R::trash($user);
			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
			$this->context->transport->success = true;
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
			$edited = false;
			PrivilegesHelper::confirmWithException(Privilege::ViewUser, PrivilegesHelper::getIdentitySubPrivilege($user));

			$this->context->handleExceptions = true;
			$this->context->transport->user = $user;
			$this->context->transport->tab = 'edit';
			$this->context->viewName = 'user-view';
			$this->context->stylesheets []= 'user-view.css';
			$this->context->subTitle = $name;

			$this->context->suppliedCurrentPassword = $suppliedCurrentPassword = InputHelper::get('current-password');
			$this->context->suppliedName = $suppliedName = InputHelper::get('name');
			$this->context->suppliedPassword1 = $suppliedPassword1 = InputHelper::get('password1');
			$this->context->suppliedPassword2 = $suppliedPassword2 = InputHelper::get('password2');
			$this->context->suppliedEmail = $suppliedEmail = InputHelper::get('email');
			$this->context->suppliedAccessRank = $suppliedAccessRank = InputHelper::get('access-rank');
			$currentPasswordHash = $user->pass_hash;

			if ($suppliedName != '' and $suppliedName != $user->name)
			{
				PrivilegesHelper::confirmWithException(Privilege::ChangeUserName, PrivilegesHelper::getIdentitySubPrivilege($user));
				$suppliedName = Model_User::validateUserName($suppliedName);
				$user->name = $suppliedName;
				$edited = true;
			}

			if ($suppliedPassword1 != '')
			{
				PrivilegesHelper::confirmWithException(Privilege::ChangeUserPassword, PrivilegesHelper::getIdentitySubPrivilege($user));
				if ($suppliedPassword1 != $suppliedPassword2)
					throw new SimpleException('Specified passwords must be the same');
				$suppliedPassword = Model_User::validatePassword($suppliedPassword1);
				$user->pass_hash = Model_User::hashPassword($suppliedPassword, $user->pass_salt);
				$edited = true;
			}

			if ($suppliedEmail != '' and $suppliedEmail != $user->email_confirmed)
			{
				PrivilegesHelper::confirmWithException(Privilege::ChangeUserEmail, PrivilegesHelper::getIdentitySubPrivilege($user));
				$suppliedEmail = Model_User::validateEmail($suppliedEmail);
				if ($this->context->user->id == $user->id)
				{
					$user->email_unconfirmed = $suppliedEmail;
					if (!empty($user->email_unconfirmed))
						self::sendEmailConfirmation($user);
				}
				else
				{
					$user->email_confirmed = $suppliedEmail;
				}
				$edited = true;
			}

			if ($suppliedAccessRank != '' and $suppliedAccessRank != $user->access_rank)
			{
				PrivilegesHelper::confirmWithException(Privilege::ChangeUserAccessRank, PrivilegesHelper::getIdentitySubPrivilege($user));
				$suppliedAccessRank = Model_User::validateAccessRank($suppliedAccessRank);
				$user->access_rank = $suppliedAccessRank;
				$edited = true;
			}

			if ($edited)
			{
				if ($this->context->user->id == $user->id)
				{
					$suppliedPasswordHash = Model_User::hashPassword($suppliedCurrentPassword, $user->pass_salt);
					if ($suppliedPasswordHash != $currentPasswordHash)
						throw new SimpleException('Must supply valid current password');
				}
				R::store($user);
				$this->context->transport->success = true;
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
		$this->context->stylesheets []= 'user-view.css';
		$this->context->stylesheets []= 'post-list.css';
		$this->context->stylesheets []= 'paginator.css';
		if ($this->config->browsing->endlessScrolling)
			$this->context->scripts []= 'paginator-endless.js';
		$this->context->subTitle = $name;

		$buildDbQuery = function($dbQuery, $user, $tab)
		{
			$dbQuery->from('post');


			/* safety */
			$allowedSafety = array_filter(PostSafety::getAll(), function($safety)
			{
				return PrivilegesHelper::confirm(Privilege::ListPosts, PostSafety::toString($safety)) and
					$this->context->user->hasEnabledSafety($safety);
			});
			$dbQuery->where('safety IN (' . R::genSlots($allowedSafety) . ')');
			foreach ($allowedSafety as $s)
				$dbQuery->put($s);


			/* hidden */
			if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden'))
				$dbQuery->andNot('hidden');


			/* tab */
			switch ($tab)
			{
				case 'uploads':
					$dbQuery
						->and('uploader_id = ?')
						->put($user->id);
					break;
				case 'favs':
					$dbQuery
						->and()
						->exists()
						->open()
						->select('1')
						->from('favoritee')
						->where('post_id = post.id')
						->and('favoritee.user_id = ?')
						->put($user->id)
						->close();
					break;
			}
		};

		$countDbQuery = R::$f->begin()->select('COUNT(*)')->as('count');
		$buildDbQuery($countDbQuery, $user, $tab);
		$postCount = intval($countDbQuery->get('row')['count']);
		$pageCount = ceil($postCount / $postsPerPage);
		$page = max(1, min($pageCount, $page));

		$searchDbQuery = R::$f->begin()->select('*');
		$buildDbQuery($searchDbQuery, $user, $tab);
		$searchDbQuery->orderBy('id DESC')
			->limit('?')
			->put($postsPerPage)
			->offset('?')
			->put(($page - 1) * $postsPerPage);

		$posts = $searchDbQuery->get();
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
		if (!$this->context->loggedIn)
			throw new SimpleException('Not logged in');

		if (!in_array($safety, PostSafety::getAll()))
			throw new SimpleExcetpion('Invalid safety');

		$this->context->user->enableSafety($safety,
			!$this->context->user->hasEnabledSafety($safety));

		R::store($this->context->user);

		$this->context->transport->success = true;
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

		if ($suppliedName !== null)
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

			//prepare unique registration token
			do
			{
				$emailToken =  md5(mt_rand() . uniqid());
			}
			while (R::findOne('user', 'email_token = ?', [$emailToken]) !== null);
			$dbUser->email_token = $emailToken;

			$dbUser->join_date = time();
			if (R::findOne('user') === null)
			{
				$dbUser->access_rank = AccessRank::Admin;
				$dbUser->staff_confirmed = true;
				$dbUser->email_confirmed = $suppliedEmail;
			}
			else
			{
				$dbUser->access_rank = AccessRank::Registered;
				$dbUser->staff_confirmed = false;
				$dbUser->staff_confirmed = null;
				if (!empty($dbUser->email_unconfirmed))
					self::sendEmailConfirmation($dbUser);
			}

			//save the user to db if everything went okay
			R::store($dbUser);
			$this->context->transport->success = true;

			if (!$this->config->registration->needEmailForRegistering and !$this->config->registration->staffActivation)
			{
				$_SESSION['user-id'] = $dbUser->id;
				\Chibi\Registry::getBootstrap()->attachUser();
			}
		}
	}



	/**
	* @route /activation/{token}
	*/
	public function activationAction($token)
	{
		$this->context->subTitle = 'account activation';

		if (empty($token))
			throw new SimpleException('Invalid activation token');

		$dbUser = R::findOne('user', 'email_token = ?', [$token]);
		if ($dbUser === null)
			throw new SimpleException('No user with such activation token');

		if (!$dbUser->email_unconfirmed)
			throw new SimpleException('This user was already activated');

		$dbUser->email_confirmed = $dbUser->email_unconfirmed;
		$dbUser->email_unconfirmed = null;
		R::store($dbUser);
		$this->context->transport->success = true;

		if (!$this->config->registration->staffActivation)
		{
			$_SESSION['user-id'] = $dbUser->id;
			\Chibi\Registry::getBootstrap()->attachUser();
		}
	}
}
