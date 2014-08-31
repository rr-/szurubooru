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

	public $name;
	public $passwordHash;
	public $email;
	public $registrationDate;
	public $lastLoginTime;
}
