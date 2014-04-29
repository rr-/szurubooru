<?php
class PrivilegesHelper
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

	public static function confirm($privilege, $subPrivilege = null)
	{
		if (php_sapi_name() == 'cli')
			return true;

		$user = getContext()->user;
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

	public static function confirmWithException($privilege, $subPrivilege = null)
	{
		if (!self::confirm($privilege, $subPrivilege))
			throw new SimpleException('Insufficient privileges');
	}

	public static function getIdentitySubPrivilege($user)
	{
		if (!$user)
			return 'all';
		$userFromContext = getContext()->user;
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

		$context = getContext();
		return array_filter(PostSafety::getAll(), function($safety) use ($context)
		{
			return PrivilegesHelper::confirm(Privilege::ListPosts, PostSafety::toString($safety)) and
				$context->user->hasEnabledSafety($safety);
		});
	}
}

PrivilegesHelper::init();
