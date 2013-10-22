<?php
class AuthController
{
	public static function tryLogin($name, $password)
	{
		$config = \Chibi\Registry::getConfig();

		$dbUser = R::findOne('user', 'name = ?', [$name]);
		if ($dbUser === null)
			throw new SimpleException('Invalid username');

		$passwordHash = Model_User::hashPassword($password, $dbUser->pass_salt);
		if ($passwordHash != $dbUser->pass_hash)
			throw new SimpleException('Invalid password');

		if (!$dbUser->staff_confirmed and $config->registration->staffActivation)
			throw new SimpleException('Staff hasn\'t confirmed your registration yet');

		if ($dbUser->banned)
			throw new SimpleException('You are banned');

		if ($config->registration->needEmailForRegistering)
			PrivilegesHelper::confirmEmail($dbUser);

		$_SESSION['user-id'] = $dbUser->id;
		$_SESSION['user'] = serialize($dbUser);
		\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
		return $dbUser;
	}

	public static function tryAutoLogin()
	{
		if (!isset($_COOKIE['auth']))
			return;

		$token = TextHelper::decrypt($_COOKIE['auth']);
		list ($name, $password) = array_map('base64_decode', explode('|', $token));
		return self::tryLogin($name, $password);
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

		if (InputHelper::get('submit'))
		{
			$suppliedName = InputHelper::get('name');
			$suppliedPassword = InputHelper::get('password');
			$dbUser = self::tryLogin($suppliedName, $suppliedPassword);

			if (InputHelper::get('remember'))
			{
				$token = implode('|', [base64_encode($suppliedName), base64_encode($suppliedPassword)]);
				setcookie('auth', TextHelper::encrypt($token), time() + 365 * 24 * 3600, '/');
			}
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
		setcookie('auth', false, 0, '/');
		\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
	}
}
