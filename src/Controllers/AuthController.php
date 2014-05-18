<?php
class AuthController extends AbstractController
{
	public function loginView()
	{
		if (Auth::isLoggedIn())
			$this->redirectToLastVisitedUrl('auth');
		else
			$this->renderView('auth-login');
	}

	public function loginAction()
	{
		try
		{
			$suppliedName = InputHelper::get('name');
			$suppliedPassword = InputHelper::get('password');
			$remember = boolval(InputHelper::get('remember'));
			Auth::login($suppliedName, $suppliedPassword, $remember);
		}
		catch (SimpleException $e)
		{
			Messenger::fail($e->getMessage());
			$this->renderView('auth-login');
		}

		$this->redirectToLastVisitedUrl('auth');
	}

	public function logoutAction()
	{
		Auth::logout();
		$this->redirectToLastVisitedUrl('auth');
	}
}
