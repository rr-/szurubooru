<?php
class AuthController
{
	private static function redirectAfterLog()
	{
		if (isset($_SESSION['login-redirect-url']))
		{
			\Chibi\UrlHelper::forward($_SESSION['login-redirect-url']);
			unset($_SESSION['login-redirect-url']);
			return;
		}
		\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
	}

	public static function tryLogin($name, $password)
	{
		$config = \Chibi\Registry::getConfig();
		$context = \Chibi\Registry::getContext();

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

		$context->user = $dbUser;
		self::doReLog();
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
			self::redirectAfterLog();
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
			self::redirectAfterLog();
		}
	}

	/**
	* @route /auth/logout
	*/
	public function logoutAction()
	{
		$this->context->viewName = null;
		$this->context->layoutName = null;
		self::doLogOut();
		setcookie('auth', false, 0, '/');
		\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
	}

	public static function doLogOut()
	{
		unset($_SESSION['user']);
	}

	public static function doLogIn()
	{
		$context = \Chibi\Registry::getContext();
		if (!isset($_SESSION['user']))
		{
			if (!empty($context->user) and $context->user->id)
			{
				$dbUser = R::findOne('user', 'id = ?', [$context->user->id]);
				$_SESSION['user'] = serialize($dbUser);
			}
			else
			{
				$dummy = R::dispense('user');
				$dummy->name = 'Anonymous';
				$dummy->access_rank = AccessRank::Anonymous;
				$dummy->anonymous = true;
				$_SESSION['user'] = serialize($dummy);
			}
		}
		$context->user = unserialize($_SESSION['user']);
		$context->loggedIn = $context->user->anonymous ? false : true;
		if (!$context->loggedIn)
		{
			try
			{
				self::tryAutoLogin();
			}
			catch (Exception $e)
			{
			}
		}
	}

	public static function doReLog()
	{
		$context = \Chibi\Registry::getContext();
		if ($context->user !== null)
			$_SESSION['user'] = serialize($context->user);
		self::doLogIn();
	}

	public static function observeWorkFinish()
	{
		if (strpos(\Chibi\HeadersHelper::get('Content-Type'), 'text/html') === false)
			return;
		$context = \Chibi\Registry::getContext();
		if ($context->route->simpleControllerName == 'auth')
			return;
		$_SESSION['login-redirect-url'] = $context->query;
	}
}
