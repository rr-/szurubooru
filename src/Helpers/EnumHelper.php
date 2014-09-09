<?php
namespace Szurubooru\Helpers;

class EnumHelper
{
	public static function accessRankToString($accessRank)
	{
		$map =
		[
			\Szurubooru\Entities\User::ACCESS_RANK_ANONYMOUS => 'anonymous',
			\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER => 'regularUser',
			\Szurubooru\Entities\User::ACCESS_RANK_POWER_USER => 'powerUser',
			\Szurubooru\Entities\User::ACCESS_RANK_MODERATOR => 'moderator',
			\Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR => 'administrator',
		];

		if (!isset($map[$accessRank]))
			throw new \DomainException('Invalid access rank!');

		return $map[$accessRank];
	}

	public static function accessRankFromString($accessRankString)
	{
		$map =
		[
			'anonymous' => \Szurubooru\Entities\User::ACCESS_RANK_ANONYMOUS,
			'regularUser' => \Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER,
			'powerUser' => \Szurubooru\Entities\User::ACCESS_RANK_POWER_USER,
			'moderator' => \Szurubooru\Entities\User::ACCESS_RANK_MODERATOR,
			'administrator' => \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR,
		];

		$key = trim(strtolower($accessRankString));
		if (!isset($map[$key]))
			throw new \DomainException('Unrecognized access rank: ' . $accessRankString);

		return $map[$key];
	}

	public static function avatarStyleFromString($avatarStyleString)
	{
		$map =
		[
			'gravatar' => \Szurubooru\Entities\User::AVATAR_STYLE_GRAVATAR,
			'manual' => \Szurubooru\Entities\User::AVATAR_STYLE_MANUAL,
			'none' => \Szurubooru\Entities\User::AVATAR_STYLE_BLANK,
			'blank' => \Szurubooru\Entities\User::AVATAR_STYLE_BLANK,
		];

		$key = trim(strtolower($avatarStyleString));
		if (!isset($map[$key]))
			throw new \DomainException('Unrecognized avatar style: ' . $avatarStyleString);

		return $map[$key];
	}
}
