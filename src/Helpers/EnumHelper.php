<?php
namespace Szurubooru\Helpers;

class EnumHelper
{
	private static $accessRankMap =
	[
		'anonymous' => \Szurubooru\Entities\User::ACCESS_RANK_ANONYMOUS,
		'regularUser' => \Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER,
		'powerUser' => \Szurubooru\Entities\User::ACCESS_RANK_POWER_USER,
		'moderator' => \Szurubooru\Entities\User::ACCESS_RANK_MODERATOR,
		'administrator' => \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR,
	];

	private static $avatarStyleMap =
	[
		'gravatar' => \Szurubooru\Entities\User::AVATAR_STYLE_GRAVATAR,
		'manual' => \Szurubooru\Entities\User::AVATAR_STYLE_MANUAL,
		'none' => \Szurubooru\Entities\User::AVATAR_STYLE_BLANK,
		'blank' => \Szurubooru\Entities\User::AVATAR_STYLE_BLANK,
	];

	public static function accessRankToString($accessRank)
	{
		return self::enumToString(self::$accessRankMap, $accessRank);
	}

	public static function accessRankFromString($accessRankString)
	{
		return self::stringToEnum(self::$accessRankMap, $accessRankString);
	}

	public static function avatarStyleToString($avatarStyle)
	{
		return self::enumToString(self::$avatarStyleMap, $avatarStyle);
	}

	public static function avatarStyleFromString($avatarStyleString)
	{
		return self::stringToEnum(self::$avatarStyleMap, $avatarStyleString);
	}

	private static function enumToString($enumMap, $enumValue)
	{
		$reverseMap = array_flip($enumMap);
		if (!isset($reverseMap[$enumValue]))
			throw new \RuntimeException('Invalid value!');

		return $reverseMap[$enumValue];
	}

	private static function stringToEnum($enumMap, $enumString)
	{
		$key = trim(strtolower($enumString));
		if (!isset($enumMap[$key]))
			throw new \DomainException('Unrecognized avatar style: ' . $enumString);

		return $enumMap[$key];
	}
}
