<?php
class AuthController
{
	public function loginView()
	{
		$context = getContext();
		$context->handleExceptions = true;

		//check if already logged in
		if (Auth::isLoggedIn())
			self::redirect();
	}

	public function loginAction()
	{
		$suppliedName = InputHelper::get('name');
		$suppliedPassword = InputHelper::get('password');
		$remember = boolval(InputHelper::get('remember'));
		Auth::login($suppliedName, $suppliedPassword, $remember);
		self::redirect();
	}

	public function logoutAction()
	{
		Auth::logout();
		self::redirect();
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

	private static function redirect()
	{
		if (isset($_SESSION['login-redirect-url']))
		{
			\Chibi\Util\Url::forward(\Chibi\Util\Url::makeAbsolute($_SESSION['login-redirect-url']));
			unset($_SESSION['login-redirect-url']);
			return;
		}
		\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['StaticPagesController', 'mainPageView']));
	}
}
