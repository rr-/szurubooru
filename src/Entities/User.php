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

	const AVATAR_STYLE_GRAVATAR = 1;
	const AVATAR_STYLE_MANUAL = 2;
	const AVATAR_STYLE_BLANK = 3;

	const LAZY_LOADER_CUSTOM_AVATAR_SOURCE_CONTENT = 'customAvatarContent';

	protected $name;
	protected $email;
	protected $emailUnconfirmed;
	protected $passwordHash;
	protected $passwordSalt;
	protected $accessRank;
	protected $registrationTime;
	protected $lastLoginTime;
	protected $avatarStyle;
	protected $browsingSettings;
	protected $accountConfirmed = false;
	protected $banned = false;

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

	public function isBanned()
	{
		return $this->banned;
	}

	public function setBanned($banned)
	{
		$this->banned = boolval($banned);
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

	public function getPasswordSalt()
	{
		return $this->passwordSalt;
	}

	public function setPasswordSalt($passwordSalt)
	{
		$this->passwordSalt = $passwordSalt;
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

	public function getCustomAvatarSourceContent()
	{
		return $this->lazyLoad(self::LAZY_LOADER_CUSTOM_AVATAR_SOURCE_CONTENT, null);
	}

	public function setCustomAvatarSourceContent($content)
	{
		$this->lazySave(self::LAZY_LOADER_CUSTOM_AVATAR_SOURCE_CONTENT, $content);
	}

	public function getCustomAvatarSourceContentPath()
	{
		return 'avatars' . DIRECTORY_SEPARATOR . $this->getId();
	}
}
