<?php
namespace Szurubooru\Helpers;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Entities\User;

class EnumHelper
{
    private static $accessRankMap =
    [
        'anonymous' => User::ACCESS_RANK_ANONYMOUS,
        'restrictedUser' => User::ACCESS_RANK_RESTRICTED_USER,
        'regularUser' => User::ACCESS_RANK_REGULAR_USER,
        'powerUser' => User::ACCESS_RANK_POWER_USER,
        'moderator' => User::ACCESS_RANK_MODERATOR,
        'administrator' => User::ACCESS_RANK_ADMINISTRATOR,
    ];

    private static $avatarStyleMap =
    [
        'gravatar' => User::AVATAR_STYLE_GRAVATAR,
        'manual' => User::AVATAR_STYLE_MANUAL,
        'none' => User::AVATAR_STYLE_BLANK,
        'blank' => User::AVATAR_STYLE_BLANK,
    ];

    private static $postSafetyMap =
    [
        'safe' => Post::POST_SAFETY_SAFE,
        'sketchy' => Post::POST_SAFETY_SKETCHY,
        'unsafe' => Post::POST_SAFETY_UNSAFE,
    ];

    private static $postTypeMap =
    [
        'image' => Post::POST_TYPE_IMAGE,
        'video' => Post::POST_TYPE_VIDEO,
        'flash' => Post::POST_TYPE_FLASH,
        'youtube' => Post::POST_TYPE_YOUTUBE,
        'animation' => Post::POST_TYPE_ANIMATED_IMAGE,
    ];

    private static $snapshotTypeMap =
    [
        'post' => Snapshot::TYPE_POST,
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

    public static function postTypeFromString($postTypeString)
    {
        return self::stringToEnum(self::$postTypeMap, $postTypeString);
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
        {
            throw new \DomainException(sprintf(
                'Unrecognized value: %s.' . PHP_EOL . 'Possible values: %s',
                $enumString,
                implode(', ', array_keys($lowerEnumMap))));
        }

        return $lowerEnumMap[$key];
    }
}
