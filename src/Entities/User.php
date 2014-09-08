<?php
namespace Szurubooru\Entities;

final class User extends Entity
{
	const ACCESS_RANK_NOBODY = 0;
	const ACCESS_RANK_ANONYMOUS = 1;
	const ACCESS_RANK_REGULAR_USER = 2;
	const ACCESS_RANK_POWER_USER = 3;
	const ACCESS_RANK_MODERATOR = 4;
	const ACCESS_RANK_ADMINISTRATOR = 5;

	const AVATAR_STYLE_GRAVATAR = 'gravatar';
	const AVATAR_STYLE_MANUAL = 'manual';
	const AVATAR_STYLE_BLANK = 'blank';

	public $name;
	public $email;
	public $emailUnconfirmed;
	public $passwordHash;
	public $accessRank;
	public $registrationTime;
	public $lastLoginTime;
	public $avatarStyle;
	public $browsingSettings;
}
