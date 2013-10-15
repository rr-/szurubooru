<?php
class AuthController
{
	/**
	* @route /auth/login
	*/
	public function loginAction()
	{
		$this->context->handleExceptions = true;
		$this->context->stylesheets []= 'auth.css';
		$this->context->subTitle = 'authentication form';

		//check if already logged in
		if ($this->context->loggedIn)
		{
			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
			return;
		}

		$suppliedName = InputHelper::get('name');
		$suppliedPassword = InputHelper::get('password');
		if ($suppliedName !== null and $suppliedPassword !== null)
		{
			$dbUser = R::findOne('user', 'name = ?', [$suppliedName]);
			if ($dbUser === null)
				throw new SimpleException('Invalid username');

			$suppliedPasswordHash = Model_User::hashPassword($suppliedPassword, $dbUser->pass_salt);
			if ($suppliedPasswordHash != $dbUser->pass_hash)
				throw new SimpleException('Invalid password');

			if (!$dbUser->staff_confirmed and $this->config->registration->staffActivation)
				throw new SimpleException('Staff hasn\'t confirmed your registration yet');

			if ($dbUser->banned)
				throw new SimpleException('You are banned');

			if (!$dbUser->email_confirmed and $this->config->registration->emailActivation)
				throw new SimpleException('You haven\'t confirmed your e-mail address yet');

			$_SESSION['user-id'] = $dbUser->id;
			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
			$this->context->transport->success = true;
		}
	}

	/**
	* @route /auth/logout
	*/
	public function logoutAction()
	{
		$this->context->viewName = null;
		$this->context->viewName = null;
		unset($_SESSION['user-id']);
		\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
	}

	/**
	* @route /register
	*/
	public function registerAction()
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

		$regConfig = $this->config->registration;
		$emailActivation = $regConfig->emailActivation;
		$staffActivation = $regConfig->staffActivation;

		$this->context->transport->staffActivation = $staffActivation;
		$this->context->transport->emailActivation = $emailActivation;

		if ($suppliedName !== null)
		{
			$suppliedName = Model_User::validateUserName($suppliedName);

			if ($suppliedPassword1 != $suppliedPassword2)
				throw new SimpleException('Specified passwords must be the same');
			$suppliedPassword = Model_User::validatePassword($suppliedPassword1);

			$suppliedEmail = Model_User::validateEmail($suppliedEmail);
			if (empty($suppliedEmail) and $emailActivation)
				throw new SimpleException('E-mail address is required - you will be sent confirmation e-mail.');

			//register the user
			$dbUser = R::dispense('user');
			$dbUser->name = $suppliedName;
			$dbUser->pass_salt = md5(mt_rand() . uniqid());
			$dbUser->pass_hash = Model_User::hashPassword($suppliedPassword, $dbUser->pass_salt);
			$dbUser->email = $suppliedEmail;
			$dbUser->join_date = time();
			if (R::findOne('user') === null)
			{
				$dbUser->staff_confirmed = true;
				$dbUser->email_confirmed = true;
				$dbUser->access_rank = AccessRank::Admin;
			}
			else
			{
				$dbUser->staff_confirmed = false;
				$dbUser->email_confirmed = false;
				$dbUser->access_rank = AccessRank::Registered;
			}

			//prepare unique registration token
			do
			{
				$emailToken =  md5(mt_rand() . uniqid());
			}
			while (R::findOne('user', 'email_token = ?', [$emailToken]) !== null);
			$dbUser->email_token = $emailToken;

			//send the e-mail
			if ($emailActivation)
			{
				$tokens = [];
				$tokens['host'] = $_SERVER['HTTP_HOST'];
				$tokens['link'] = \Chibi\UrlHelper::route('auth', 'activation', ['token' => $dbUser->email_token]);

				$body = wordwrap(TextHelper::replaceTokens($regConfig->activationEmailBody, $tokens), 70);
				$subject = TextHelper::replaceTokens($regConfig->activationEmailSubject, $tokens);
				$senderName = TextHelper::replaceTokens($regConfig->activationEmailSenderName, $tokens);
				$senderEmail = $regConfig->activationEmailSenderEmail;

				$headers = [];
				$headers[] = sprintf('From: %s <%s>', $senderName, $senderEmail);
				$headers[] = sprintf('Subject: %s', $subject);
				$headers[] = sprintf('X-Mailer: PHP/%s', phpversion());
				mail($dbUser->email, $subject, $body, implode("\r\n", $headers));
			}

			//save the user to db if everything went okay
			R::store($dbUser);
			$this->context->transport->success = true;

			if (!$emailActivation and !$staffActivation)
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

		//check if already logged in
		if ($this->context->loggedIn)
		{
			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
			return;
		}

		if (empty($token))
			throw new SimpleException('Invalid activation token');

		$dbUser = R::findOne('user', 'email_token = ?', [$token]);
		if ($dbUser === null)
			throw new SimpleException('No user with such activation token');

		if ($dbUser->email_confirmed)
			throw new SimpleException('This user was already activated');

		$dbUser->email_confirmed = true;
		R::store($dbUser);
		$this->context->transport->success = true;

		$staffActivation = $this->config->registration->staffActivation;
		$this->context->transport->staffActivation = $staffActivation;
		if (!$staffActivation)
		{
			$_SESSION['user-id'] = $dbUser->id;
			\Chibi\Registry::getBootstrap()->attachUser();
		}
	}
}
