<?php
class Model_User extends RedBean_SimpleModel
{
	public function getAvatarUrl($size = 32)
	{
		$subject = !empty($this->email)
			? $this->email
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

	public function hasEnabledSafety($safety)
	{
		return $this->getSetting('safety-' . $safety) !== false;
	}

	public function enableSafety($safety, $enabled)
	{
		if (!$enabled)
		{
			$this->setSetting('safety-' . $safety, false);
			$anythingEnabled = false;
			foreach (PostSafety::getAll() as $safety)
				if (self::hasEnabledSafety($safety))
					$anythingEnabled = true;
			if (!$anythingEnabled)
				$this->setSetting('safety-' . PostSafety::Safe, true);
		}
		else
		{
			$this->setSetting('safety-' . $safety, true);
		}
	}

	public static function validateUserName($userName)
	{
		$userName = trim($userName);

		$dbUser = R::findOne('user', 'name = ?', [$userName]);
		if ($dbUser !== null)
		{
			if (!$dbUser->email_confirmed and \Chibi\Registry::getConfig()->registration->emailActivation)
				throw new SimpleException('User with this name is already registered and awaits e-mail confirmation');

			if (!$dbUser->staff_confirmed and \Chibi\Registry::getConfig()->registration->staffActivation)
			throw new SimpleException('User with this name is already registered and awaits staff confirmation');

			throw new SimpleException('User with this name is already registered');
		}

		$userNameMinLength = intval(\Chibi\Registry::getConfig()->registration->userNameMinLength);
		$userNameRegex = \Chibi\Registry::getConfig()->registration->userNameRegex;

		if (strlen($userName) < $userNameMinLength)
			throw new SimpleException(sprintf('User name must have at least %d characters', $userNameMinLength));

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

		return $accessRank;
	}

	public static function hashPassword($pass, $salt2)
	{
		$salt1 = \Chibi\Registry::getConfig()->registration->salt;
		return sha1($salt1 . $salt2 . $pass);
	}

}
