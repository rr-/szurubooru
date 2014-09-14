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

	protected $name;
	protected $email;
	protected $emailUnconfirmed;
	protected $passwordHash;
	protected $accessRank;
	protected $registrationTime;
	protected $lastLoginTime;
	protected $avatarStyle;
	protected $browsingSettings;
	protected $accountConfirmed = false;

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setEmail($email)
	{
		$this->email = $email;
	}

	public function getEmailUnconfirmed()
	{
		return $this->emailUnconfirmed;
	}

	public function setEmailUnconfirmed($emailUnconfirmed)
	{
		$this->emailUnconfirmed = $emailUnconfirmed;
	}

	public function isAccountConfirmed()
	{
		return $this->accountConfirmed;
	}

	public function setAccountConfirmed($accountConfirmed)
	{
		$this->accountConfirmed = boolval($accountConfirmed);
	}

	public function getPasswordHash()
	{
		return $this->passwordHash;
	}

	public function setPasswordHash($passwordHash)
	{
		$this->passwordHash = $passwordHash;
	}

	public function getAccessRank()
	{
		return $this->accessRank;
	}

	public function setAccessRank($accessRank)
	{
		$this->accessRank = $accessRank;
	}

	public function getRegistrationTime()
	{
		return $this->registrationTime;
	}

	public function setRegistrationTime($registrationTime)
	{
		$this->registrationTime = $registrationTime;
	}

	public function getLastLoginTime()
	{
		return $this->lastLoginTime;
	}

	public function setLastLoginTime($lastLoginTime)
	{
		$this->lastLoginTime = $lastLoginTime;
	}

	public function getAvatarStyle()
	{
		return $this->avatarStyle;
	}

	public function setAvatarStyle($avatarStyle)
	{
		$this->avatarStyle = $avatarStyle;
	}

	public function getBrowsingSettings()
	{
		return $this->browsingSettings;
	}

	public function setBrowsingSettings($browsingSettings)
	{
		$this->browsingSettings = $browsingSettings;
	}
}
