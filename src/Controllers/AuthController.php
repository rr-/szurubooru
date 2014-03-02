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

		$dbUser = UserModel::findByNameOrEmail($name, false);
		if ($dbUser === null)
			throw new SimpleException('Invalid username');

		$passwordHash = UserModel::hashPassword($password, $dbUser->passSalt);
		if ($passwordHash != $dbUser->passHash)
			throw new SimpleException('Invalid password');

		if (!$dbUser->staffConfirmed and $config->registration->staffActivation)
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
			StatusHelper::success();
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
				$dbUser = UserModel::findById($context->user->id);
				$dbUser->lastLoginDate = time();
				UserModel::save($dbUser);
				$_SESSION['user'] = serialize($dbUser);
			}
			else
			{
				$dummy = UserModel::spawn();
				$dummy->name = UserModel::getAnonymousName();
				$dummy->accessRank = AccessRank::Anonymous;
				$_SESSION['user'] = serialize($dummy);
			}
		}

		$context->user = unserialize($_SESSION['user']);
		$context->loggedIn = $context->user->accessRank != AccessRank::Anonymous;
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
			self::doLogOut();
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
