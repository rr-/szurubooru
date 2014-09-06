<?php
namespace Szurubooru\Helpers;

class EnumHelper
{
	public static function accessRankToString($accessRank)
	{
		switch ($accessRank)
		{
			case \Szurubooru\Entities\User::ACCESS_RANK_ANONYMOUS: return 'anonymous'; break;
			case \Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER: return 'regularUser'; break;
			case \Szurubooru\Entities\User::ACCESS_RANK_POWER_USER: return 'powerUser'; break;
			case \Szurubooru\Entities\User::ACCESS_RANK_MODERATOR: return 'moderator'; break;
			case \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR: return 'administrator'; break;
			default:
				throw new \DomainException('Invalid access rank!');
		}
	}
}
