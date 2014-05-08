<?php
class Auth
{
	public static function logOut()
	{
		self::setCurrentUser(null);

		setcookie('auth', false, 0, '/');
	}

	public static function login($name, $password, $remember)
	{
		$config = getConfig();
		$context = getContext();

		$user = UserModel::tryGetByNameOrEmail($name);
		if ($user === null)
			throw new SimpleException('Invalid username');

		$passwordHash = UserModel::hashPassword($password, $user->getPasswordSalt());
		if ($passwordHash != $user->getPasswordHash())
			throw new SimpleException('Invalid password');

		if (!$user->isStaffConfirmed() and $config->registration->staffActivation)
			throw new SimpleException('Staff hasn\'t confirmed your registration yet');

		if ($user->isBanned())
			throw new SimpleException('You are banned');

		if ($config->registration->needEmailForRegistering)
			Access::assertEmailConfirmation($user);

		if ($remember)
		{
			$token = implode('|', [base64_encode($name), base64_encode($password)]);
			setcookie('auth', TextHelper::encrypt($token), time() + 365 * 24 * 3600, '/');
		}

		self::setCurrentUser($user);
	}

	public static function tryAutoLogin()
	{
		if (!isset($_COOKIE['auth']))
			return;

		$token = TextHelper::decrypt($_COOKIE['auth']);
		list ($name, $password) = array_map('base64_decode', explode('|', $token));
		try
		{
			self::login($name, $password, false);
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	public static function isLoggedIn()
	{
		return isset($_SESSION['logged-in']) and $_SESSION['logged-in'];
	}

	public static function setCurrentUser($user)
	{
		if ($user == null)
		{
			self::setCurrentUser(self::getAnonymousUser());
		}
		else
		{
			$_SESSION['logged-in'] = $user->getAccessRank()->toInteger() != AccessRank::Anonymous;
			$_SESSION['user'] = serialize($user);
		}
	}

	public static function getCurrentUser()
	{
		return self::isLoggedIn()
			? unserialize($_SESSION['user'])
			: self::getAnonymousUser();
	}

	private static function getAnonymousUser()
	{
		$dummy = UserModel::spawn();
		$dummy->setId(null);
		$dummy->setName(UserModel::getAnonymousName());
		$dummy->setAccessRank(new AccessRank(AccessRank::Anonymous));
		return $dummy;
	}
}
