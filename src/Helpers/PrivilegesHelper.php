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
			list ($privilegeName, $flag) = explode('.', $key);
			$privilegeName = TextHelper::camelCaseToKebabCase($privilegeName);
			$flag = TextHelper::camelCaseToKebabCase($flag);
			$key = rtrim($privilegeName . '.' . $flag, '.');

			$minAccessRank = TextHelper::resolveConstant($minAccessRankName, 'AccessRank');
			self::$privileges[$key] = $minAccessRank;
		}
	}

	public static function confirm($user, $privilege, $flag = null)
	{
		$minAccessRank = AccessRank::Admin;

		$key = TextHelper::camelCaseToKebabCase(Privilege::toString($privilege));
		if (isset(self::$privileges[$key]))
		{
			$minAccessRank = self::$privileges[$key];
		}
		if ($flag != null)
		{
			$key2 = $key . '.' . strtolower($flag);
			if (isset(self::$privileges[$key2]))
			{
				$minAccessRank = self::$privileges[$key2];
			}
		}

		return intval($user->access_rank) >= $minAccessRank;
	}

	public static function confirmWithException($user, $privilege, $flag = null)
	{
		if (!self::confirm($user, $privilege, $flag))
		{
			throw new SimpleException('Insufficient privileges');
		}
	}
}

PrivilegesHelper::init();
