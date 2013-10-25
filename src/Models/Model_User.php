<?php
class Model_User extends RedBean_SimpleModel
{
	public static function locate($key, $throw = true)
	{
		$user = R::findOne('user', 'name = ?', [$key]);
		if (!$user)
		{
			if ($throw)
				throw new SimpleException('Invalid user name "' . $key . '"');
			return null;
		}
		return $user;
	}

	public function getAvatarUrl($size = 32)
	{
		$subject = !empty($this->email_confirmed)
			? $this->email_confirmed
			: $this->pass_salt . $this->name;
		$hash = md5(strtolower(trim($subject)));
		$url = 'http://www.gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=retro';
		return $url;
	}

	public function getSetting($key)
	{
		$settings = json_decode($this->settings, true);
		return isset($settings[$key])
			? $settings[$key]
			: null;
	}

	public function setSetting($key, $value)
	{
		$settings = json_decode($this->settings, true);
		$settings[$key] = $value;
		$settings = json_encode($settings);
		if (strlen($settings) > 200)
			throw new SimpleException('Too much data');
		$this->settings = $settings;
	}

	public function update()
	{
		$context = \Chibi\Registry::getContext();
		if ($context->user->id == $this->id)
		{
			$context->user = $this;
			unset($_SESSION['user']);
		}
	}



	const SETTING_SAFETY = 1;
	const SETTING_ENDLESS_SCROLLING = 2;

	public function hasEnabledSafety($safety)
	{
		$all = $this->getSetting(self::SETTING_SAFETY);
		if (!$all)
			return true;
		return $all & PostSafety::toFlag($safety);
	}

	public function enableSafety($safety, $enabled)
	{
		$new = $this->getSetting(self::SETTING_SAFETY);
		if (!$enabled)
		{
			$new &= ~PostSafety::toFlag($safety);
			if (!$new)
				$new = PostSafety::toFlag(PostSafety::Safe);
		}
		else
		{
			$new |= PostSafety::toFlag($safety);
		}
		$this->setSetting(self::SETTING_SAFETY, $new);
	}

	public function hasEnabledEndlessScrolling()
	{
		$ret = $this->getSetting(self::SETTING_ENDLESS_SCROLLING);
		if ($ret === null)
			$ret = \Chibi\Registry::getConfig()->browsing->endlessScrollingDefault;
		return $ret;
	}

	public function enableEndlessScrolling($enabled)
	{
		$this->setSetting(self::SETTING_ENDLESS_SCROLLING, $enabled ? 1 : 0);
	}



	public static function validateUserName($userName)
	{
		$userName = trim($userName);

		$dbUser = R::findOne('user', 'name = ?', [$userName]);
		if ($dbUser !== null)
		{
			if (!$dbUser->email_confirmed and \Chibi\Registry::getConfig()->registration->needEmailForRegistering)
				throw new SimpleException('User with this name is already registered and awaits e-mail confirmation');

			if (!$dbUser->staff_confirmed and \Chibi\Registry::getConfig()->registration->staffActivation)
				throw new SimpleException('User with this name is already registered and awaits staff confirmation');

			throw new SimpleException('User with this name is already registered');
		}

		$userNameMinLength = intval(\Chibi\Registry::getConfig()->registration->userNameMinLength);
		$userNameMaxLength = intval(\Chibi\Registry::getConfig()->registration->userNameMaxLength);
		$userNameRegex = \Chibi\Registry::getConfig()->registration->userNameRegex;

		if (strlen($userName) < $userNameMinLength)
			throw new SimpleException(sprintf('User name must have at least %d characters', $userNameMinLength));

		if (strlen($userName) > $userNameMaxLength)
			throw new SimpleException(sprintf('User name must have at most %d characters', $userNameMaxLength));

		if (!preg_match($userNameRegex, $userName))
			throw new SimpleException('User name contains invalid characters');

		return $userName;
	}

	public static function validatePassword($password)
	{
		$passMinLength = intval(\Chibi\Registry::getConfig()->registration->passMinLength);
		$passRegex = \Chibi\Registry::getConfig()->registration->passRegex;

		if (strlen($password) < $passMinLength)
			throw new SimpleException(sprintf('Password must have at least %d characters', $passMinLength));

		if (!preg_match($passRegex, $password))
			throw new SimpleException('Password contains invalid characters');

		return $password;
	}

	public static function validateEmail($email)
	{
		$email = trim($email);

		if (!empty($email) and !TextHelper::isValidEmail($email))
			throw new SimpleException('E-mail address appears to be invalid');

		return $email;
	}

	public static function validateAccessRank($accessRank)
	{
		$accessRank = intval($accessRank);

		if (!in_array($accessRank, AccessRank::getAll()))
			throw new SimpleException('Invalid access rank type "' . $accessRank . '"');

		if ($accessRank == AccessRank::Nobody)
			throw new SimpleException('Cannot set special accesss rank "' . $accessRank . '"');

		return $accessRank;
	}

	public static function hashPassword($pass, $salt2)
	{
		$salt1 = \Chibi\Registry::getConfig()->main->salt;
		return sha1($salt1 . $salt2 . $pass);
	}

}
