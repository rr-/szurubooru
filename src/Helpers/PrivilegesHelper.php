<?php
class PrivilegesHelper
{
	private static $privileges = [];

	public static function init()
	{
		self::$privileges = [];
		foreach (\Chibi\Registry::getConfig()->privileges as $key => $minAccessRankName)
		{
			if (strpos($key, '.') === false)
				$key .= '.';
			list ($privilegeName, $subPrivilegeName) = explode('.', $key);
			$privilegeName = TextHelper::camelCaseToKebabCase($privilegeName);
			$subPrivilegeName = TextHelper::camelCaseToKebabCase($subPrivilegeName);
			$key = rtrim($privilegeName . '.' . $subPrivilegeName, '.');

			$minAccessRank = TextHelper::resolveConstant($minAccessRankName, 'AccessRank');
			self::$privileges[$key] = $minAccessRank;
		}
	}

	public static function confirm($privilege, $subPrivilege = null)
	{
		if (php_sapi_name() == 'cli')
			return true;

		$user = \Chibi\Registry::getContext()->user;
		$minAccessRank = AccessRank::Admin;

		$key = TextHelper::camelCaseToKebabCase(Privilege::toString($privilege));
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

	public static function confirmWithException($privilege, $subPrivilege = null)
	{
		if (!self::confirm($privilege, $subPrivilege))
		{
			throw new SimpleException('Insufficient privileges');
		}
	}

	public static function getIdentitySubPrivilege($user)
	{
		if (!$user)
			return 'all';
		$userFromContext = \Chibi\Registry::getContext()->user;
		return $user->id == $userFromContext->id ? 'own' : 'all';
	}

	public static function confirmEmail($user)
	{
		if (!$user->emailConfirmed)
			throw new SimpleException('Need e-mail address confirmation to continue');
	}

	public static function getAllowedSafety()
	{
		if (php_sapi_name() == 'cli')
			return PostSafety::getAll();

		$context = \Chibi\Registry::getContext();
		return array_filter(PostSafety::getAll(), function($safety) use ($context)
		{
			return PrivilegesHelper::confirm(Privilege::ListPosts, PostSafety::toString($safety)) and
				$context->user->hasEnabledSafety($safety);
		});
	}
}

PrivilegesHelper::init();
