<?php
class Access
{
	private static $privileges = [];
	private static $checkPrivileges = true;

	public static function init()
	{
		self::$privileges = [];
		foreach (getConfig()->privileges as $key => $minAccessRankName)
		{
			if (strpos($key, '.') === false)
				$key .= '.';
			list ($privilegeName, $subPrivilegeName) = explode('.', $key);

			$privilegeName = TextCaseConverter::convert($privilegeName,
				TextCaseConverter::CAMEL_CASE,
				TextCaseConverter::SPINAL_CASE);
			$subPrivilegeName = TextCaseConverter::convert($subPrivilegeName,
				TextCaseConverter::CAMEL_CASE,
				TextCaseConverter::SPINAL_CASE);

			$key = rtrim($privilegeName . '.' . $subPrivilegeName, '.');

			$minAccessRank = TextHelper::resolveConstant($minAccessRankName, 'AccessRank');
			self::$privileges[$key] = $minAccessRank;

			if (!isset(self::$privileges[$privilegeName]))
			{
				self::$privileges[$privilegeName] = $minAccessRank;
			}
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

		$minAccessRank = AccessRank::Nobody;

		$key = TextCaseConverter::convert($privilege->toString(),
			TextCaseConverter::CAMEL_CASE,
			TextCaseConverter::SPINAL_CASE);

		$privilege->secondary = null;
		$key2 = TextCaseConverter::convert($privilege->toString(),
			TextCaseConverter::CAMEL_CASE,
			TextCaseConverter::SPINAL_CASE);

		if (isset(self::$privileges[$key]))
			$minAccessRank = self::$privileges[$key];
		elseif (isset(self::$privileges[$key2]))
			$minAccessRank = self::$privileges[$key2];

		return $user->getAccessRank()->toInteger() >= $minAccessRank;
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
			self::fail('Insufficient privileges (' . $privilege->toString() . ')');
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

	public static function disablePrivilegeChecking()
	{
		self::$checkPrivileges = false;
	}

	public static function enablePrivilegeChecking()
	{
		self::$checkPrivileges = true;
	}
}
