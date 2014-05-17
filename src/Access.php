<?php
class Access
{
	private static $privileges = [];
	private static $checkPrivileges = true;

	public static function init()
	{
		self::$privileges = [];
		foreach (Core::getConfig()->privileges as $key => $minAccessRankName)
		{
			if (strpos($key, '.') === false)
				$key .= '.';
			list ($privilegeName, $subPrivilegeName) = explode('.', $key);
			$minAccessRank = new AccessRank(TextHelper::resolveConstant($minAccessRankName, 'AccessRank'));

			if (!in_array($privilegeName, Privilege::getAllConstants()))
				throw new Exception('Invalid privilege name in config: ' . $privilegeName);

			if (!isset(self::$privileges[$privilegeName]))
			{
				self::$privileges[$privilegeName] = [];
				self::$privileges[$privilegeName][null] = $minAccessRank;
			}

			self::$privileges[$privilegeName][$subPrivilegeName] = $minAccessRank;
		}

		//todo: move to scripts etc.
		#if (php_sapi_name() == 'cli')
		#	self::disablePrivilegeChecking();
	}

	public static function check(Privilege $privilege, $user = null)
	{
		if (!self::$checkPrivileges)
			return true;

		if ($user === null)
			$user = Auth::getCurrentUser();

		$minAccessRank = new AccessRank(AccessRank::Nobody);

		if (isset(self::$privileges[$privilege->primary][$privilege->secondary]))
			$minAccessRank = self::$privileges[$privilege->primary][$privilege->secondary];

		elseif (isset(self::$privileges[$privilege->primary][null]))
			$minAccessRank = self::$privileges[$privilege->primary][null];

		return $user->getAccessRank()->toInteger() >= $minAccessRank->toInteger();
	}

	public static function checkEmailConfirmation($user = null)
	{
		if (!self::$checkPrivileges)
			return true;

		if ($user === null)
			$user = Auth::getCurrentUser();

		if (!$user->getConfirmedEmail())
			return false;
		return true;
	}

	public static function assertAuthentication()
	{
		if (!Auth::isLoggedIn())
			self::fail('Not logged in');
	}

	public static function assert(Privilege $privilege, $user = null)
	{
		if (!self::check($privilege, $user))
			self::fail('Insufficient privileges (' . $privilege->toDisplayString() . ')');
	}

	public static function assertEmailConfirmation($user = null)
	{
		if (!self::checkEmailConfirmation($user))
			self::fail('Need e-mail address confirmation to continue');
	}

	public static function fail($message)
	{
		throw new AccessException($message);
	}

	public static function getIdentity($user)
	{
		if (!$user)
			return 'all';
		return $user->getId() == Auth::getCurrentUser()->getId() ? 'own' : 'all';
	}

	public static function getAllowedSafety()
	{
		if (!self::$checkPrivileges)
			return PostSafety::getAll();

		return array_filter(PostSafety::getAll(), function($safety)
		{
			return Access::check(new Privilege(Privilege::ListPosts, $safety->toString()))
				and Auth::getCurrentUser()->getSettings()->hasEnabledSafety($safety);
		});
	}

	public static function getAllDefinedSubPrivileges($privilege)
	{
		if (!isset(self::$privileges[$privilege]))
			return null;
		return self::$privileges[$privilege];
	}

	public static function disablePrivilegeChecking()
	{
		self::$checkPrivileges = false;
	}

	public static function enablePrivilegeChecking()
	{
		self::$checkPrivileges = true;
	}
}
