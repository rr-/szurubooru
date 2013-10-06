<?php
class PrivilegesHelper
{
	private static $privileges = [];

	public static function init()
	{
		$privileges = \Chibi\Registry::getConfig()->privileges;
		foreach ($privileges as $privilegeName => $minAccessRankName)
		{
			$privilege = TextHelper::resolveConstant($privilegeName, 'Privilege');
			$minAccessRank = TextHelper::resolveConstant($minAccessRankName, 'AccessRank');
			self::$privileges[$privilege] = $minAccessRank;
		}
	}

	public static function confirm($user, $privilege)
	{
		$minAccessRank = isset(self::$privileges[$privilege])
			? AccessRank::Admin
			: self::$privileges[$privilege];
		return $user->access_rank >= $minAccessRank;
	}

	public static function confirmWithException($user, $privilege)
	{
		if (!self::confirm($user, $privilege))
		{
			throw new SimpleException('Insufficient privileges');
		}
	}
}

PrivilegesHelper::init();
