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

			if ($this->config->registration->needEmailForRegistering)
				PrivilegesHelper::confirmEmail($dbUser);

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
}
