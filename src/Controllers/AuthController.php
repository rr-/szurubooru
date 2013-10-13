<?php
class AuthController
{
	private static function hashPassword($pass, $salt2)
	{
		$salt1 = \Chibi\Registry::getConfig()->registration->salt;
		return sha1($salt1 . $salt2 . $pass);
	}

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

		$suppliedUser = InputHelper::get('user');
		$suppliedPass = InputHelper::get('pass');
		if ($suppliedUser !== null and $suppliedPass !== null)
		{
			$dbUser = R::findOne('user', 'name = ?', [$suppliedUser]);
			if ($dbUser === null)
				throw new SimpleException('Invalid username');

			$suppliedPassHash = self::hashPassword($suppliedPass, $dbUser->pass_salt);
			if ($suppliedPassHash != $dbUser->pass_hash)
				throw new SimpleException('Invalid password');

			if (!$dbUser->staff_confirmed and $this->config->registration->staffActivation)
				throw new SimpleException('Staff hasn\'t confirmed your registration yet');

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

		$suppliedUser = InputHelper::get('user');
		$suppliedPass1 = InputHelper::get('pass1');
		$suppliedPass2 = InputHelper::get('pass2');
		$suppliedEmail = InputHelper::get('email');
		$this->context->suppliedUser = $suppliedUser;
		$this->context->suppliedPass1 = $suppliedPass1;
		$this->context->suppliedPass2 = $suppliedPass2;
		$this->context->suppliedEmail = $suppliedEmail;

		$regConfig = $this->config->registration;
		$passMinLength = intval($regConfig->passMinLength);
		$passRegex = $regConfig->passRegex;
		$userNameMinLength = intval($regConfig->userNameMinLength);
		$userNameRegex = $regConfig->userNameRegex;
		$emailActivation = $regConfig->emailActivation;
		$staffActivation = $regConfig->staffActivation;

		$this->context->transport->staffActivation = $staffActivation;
		$this->context->transport->emailActivation = $emailActivation;

		if ($suppliedUser !== null)
		{
			$dbUser = R::findOne('user', 'name = ?', [$suppliedUser]);
			if ($dbUser !== null)
			{
				if (!$dbUser->email_confirmed)
					throw new SimpleException('User with this name is already registered and awaits e-mail confirmation');

				if (!$dbUser->staff_confirmed)
					throw new SimpleException('User with this name is already registered and awaits admin confirmation');

				throw new SimpleException('User with this name is already registered');
			}

			if (strlen($suppliedUser) < $userNameMinLength)
				throw new SimpleException(sprintf('User name must have at least %d characters', $userNameMinLength));

			if (!preg_match($userNameRegex, $suppliedUser))
				throw new SimpleException('User name contains invalid characters');

			if ($suppliedPass1 != $suppliedPass2)
				throw new SimpleException('Specified passwords must be the same');

			if (strlen($suppliedPass1) < $passMinLength)
				throw new SimpleException(sprintf('Password must have at least %d characters', $passMinLength));

			if (!preg_match($passRegex, $suppliedPass1))
				throw new SimpleException('Password contains invalid characters');

			if (empty($suppliedEmail) and $emailActivation)
				throw new SimpleException('E-mail address is required - you will be sent confirmation e-mail.');

			if (!empty($suppliedEmail) and !TextHelper::isValidEmail($suppliedEmail))
				throw new SimpleException('E-mail address appears to be invalid');


			//register the user
			$dbUser = R::dispense('user');
			$dbUser->name = $suppliedUser;
			$dbUser->pass_salt = md5(mt_rand() . uniqid());
			$dbUser->pass_hash = self::hashPassword($suppliedPass1, $dbUser->pass_salt);
			$dbUser->email = $suppliedEmail;
			$dbUser->staff_confirmed = $staffActivation ? false : true;
			$dbUser->email_confirmed = $emailActivation ? false : true;
			$dbUser->access_rank = R::findOne('user') === null ? AccessRank::Admin : AccessRank::Registered;

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
