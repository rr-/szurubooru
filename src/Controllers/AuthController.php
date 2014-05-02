<?php
class AuthController
{
	public function loginView()
	{
		$context = getContext();
		$context->handleExceptions = true;

		//check if already logged in
		if (Auth::isLoggedIn())
			self::redirectAfterLog();
	}

	public function loginAction()
	{
		$suppliedName = InputHelper::get('name');
		$suppliedPassword = InputHelper::get('password');
		$remember = boolval(InputHelper::get('remember'));
		Auth::login($suppliedName, $suppliedPassword, $remember);
		self::redirectAfterLog();
	}

	public function logoutAction()
	{
		Auth::logout();
		\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['IndexController', 'indexAction']));
	}

	public static function observeWorkFinish()
	{
		if (strpos(\Chibi\Util\Headers::get('Content-Type'), 'text/html') === false)
			return;
		if (\Chibi\Util\Headers::getCode() != 200)
			return;
		$context = getContext();
		if ($context->simpleControllerName == 'auth')
			return;
		$_SESSION['login-redirect-url'] = $context->query;
	}

	private static function redirectAfterLog()
	{
		if (isset($_SESSION['login-redirect-url']))
		{
			\Chibi\Util\Url::forward(\Chibi\Util\Url::makeAbsolute($_SESSION['login-redirect-url']));
			unset($_SESSION['login-redirect-url']);
			return;
		}
		\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['IndexController', 'indexAction']));
	}
}
