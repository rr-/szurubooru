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

	private static $postSafetyMap =
	[
		'safe' => \Szurubooru\Entities\Post::POST_SAFETY_SAFE,
		'sketchy' => \Szurubooru\Entities\Post::POST_SAFETY_SKETCHY,
		'unsafe' => \Szurubooru\Entities\Post::POST_SAFETY_UNSAFE,
	];

	private static $postTypeMap =
	[
		'image' => \Szurubooru\Entities\Post::POST_TYPE_IMAGE,
		'video' => \Szurubooru\Entities\Post::POST_TYPE_VIDEO,
		'flash' => \Szurubooru\Entities\Post::POST_TYPE_FLASH,
		'youtube' => \Szurubooru\Entities\Post::POST_TYPE_YOUTUBE,
	];

	private static $snapshotTypeMap =
	[
		'post' => \Szurubooru\Entities\Snapshot::TYPE_POST,
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

	public static function postSafetyToString($postSafety)
	{
		return self::enumToString(self::$postSafetyMap, $postSafety);
	}

	public static function postSafetyFromString($postSafetyString)
	{
		return self::stringToEnum(self::$postSafetyMap, $postSafetyString);
	}

	public static function postTypeToString($postType)
	{
		return self::enumToString(self::$postTypeMap, $postType);
	}

	public static function snapshotTypeFromString($snapshotTypeString)
	{
		return self::stringToEnum(self::$snapshotTypeMap, $snapshotTypeString);
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
		$lowerEnumMap = array_change_key_case($enumMap, \CASE_LOWER);
		if (!isset($lowerEnumMap[$key]))
			throw new \DomainException('Unrecognized value: ' . $enumString);

		return $lowerEnumMap[$key];
	}
}
