<?php
namespace Szurubooru\Helpers;

class EnumHelper
{
	public static function accessRankToString($accessRank)
	{
		switch ($accessRank)
		{
			case \Szurubooru\Entities\User::ACCESS_RANK_ANONYMOUS: return 'anonymous';
			case \Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER: return 'regularUser';
			case \Szurubooru\Entities\User::ACCESS_RANK_POWER_USER: return 'powerUser';
			case \Szurubooru\Entities\User::ACCESS_RANK_MODERATOR: return 'moderator';
			case \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR: return 'administrator';
			default:
				throw new \DomainException('Invalid access rank!');
		}
	}

	public static function accessRankFromString($accessRankString)
	{
		switch (trim(strtolower($accessRankString)))
		{
			case 'anonymous': return \Szurubooru\Entities\User::ACCESS_RANK_ANONYMOUS;
			case 'regularuser': return \Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER;
			case 'poweruser': return \Szurubooru\Entities\User::ACCESS_RANK_POWER_USER;
			case 'moderator': return \Szurubooru\Entities\User::ACCESS_RANK_MODERATOR;
			case 'administrator': return \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR;
			default:
				throw new \DomainException('Unrecognized access rank: ' . $accessRankString);
		}
	}

	public static function avatarStyleFromString($avatarStyleString)
	{
		switch (trim(strtolower($avatarStyleString)))
		{
			case 'gravatar': return \Szurubooru\Entities\User::AVATAR_STYLE_GRAVATAR;
			case 'manual': return \Szurubooru\Entities\User::AVATAR_STYLE_MANUAL;
			case 'none':
			case 'blank': return \Szurubooru\Entities\User::AVATAR_STYLE_BLANK;
		}
	}
}
