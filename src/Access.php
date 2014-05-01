<?php
class Access
{
	private static $privileges = [];

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

			if (!isset(self::$privileges[$privilegeName]) or
				self::$privileges[$privilegeName] > $minAccessRank)
			{
				self::$privileges[$privilegeName] = $minAccessRank;
			}
		}
	}

	public static function check($privilege, $subPrivilege = null)
	{
		if (php_sapi_name() == 'cli')
			return true;

		$user = Auth::getCurrentUser();
		$minAccessRank = AccessRank::Admin;

		$key = TextCaseConverter::convert(Privilege::toString($privilege),
			TextCaseConverter::CAMEL_CASE,
			TextCaseConverter::SPINAL_CASE);

		if (isset(self::$privileges[$key]))
		{
			$minAccessRank = self::$privileges[$key];
		}
		if ($subPrivilege != null)
		{
			$key2 = $key . '.' . strtolower($subPrivilege);
			if (isset(self::$privileges[$key2]))
			{
				$minAccessRank = self::$privileges[$key2];
			}
		}

		return intval($user->accessRank) >= $minAccessRank;
	}

	public static function assertAuthentication()
	{
		if (!Auth::isLoggedIn())
			throw new SimpleException('Not logged in');
	}

	public static function assert($privilege, $subPrivilege = null)
	{
		if (!self::check($privilege, $subPrivilege))
			throw new SimpleException('Insufficient privileges');
	}

	public static function assertEmailConfirmation()
	{
		$user = Auth::getCurrentUser();
		if (!$user->emailConfirmed)
			throw new SimpleException('Need e-mail address confirmation to continue');
	}

	public static function getIdentity($user)
	{
		if (!$user)
			return 'all';
		return $user->id == Auth::getCurrentUser()->id ? 'own' : 'all';
	}

	public static function getAllowedSafety()
	{
		if (php_sapi_name() == 'cli')
			return PostSafety::getAll();

		return array_filter(PostSafety::getAll(), function($safety)
		{
			return Access::check(Privilege::ListPosts, PostSafety::toString($safety))
				and Auth::getCurrentUser()->hasEnabledSafety($safety);
		});
	}
}

Access::init();
