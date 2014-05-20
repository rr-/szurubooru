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
		$success = $this->interceptErrors(function()
		{
			$suppliedName = InputHelper::get('name');
			$suppliedPassword = InputHelper::get('password');
			$remember = boolval(InputHelper::get('remember'));
			Auth::login($suppliedName, $suppliedPassword, $remember);
		});

		if ($success)
			$this->redirectToLastVisitedUrl('auth');
		else
			$this->renderView('auth-login');
	}

	public function logoutAction()
	{
		Auth::logout();
		$this->redirectToLastVisitedUrl('auth');
	}
}
