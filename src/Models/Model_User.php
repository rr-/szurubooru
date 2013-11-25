<?php
class Model_User extends AbstractModel
{
	const SETTING_SAFETY = 1;
	const SETTING_ENDLESS_SCROLLING = 2;
	const SETTING_POST_TAG_TITLES = 3;



	public static function getTableName()
	{
		return 'user';
	}

	public static function getQueryBuilder()
	{
		return 'Model_User_QueryBuilder';
	}



	public static function locate($key, $throw = true)
	{
		$user = R::findOne(self::getTableName(), 'LOWER(name) = LOWER(?)', [$key]);
		if ($user)
			return $user;

		$user = R::findOne(self::getTableName(), 'LOWER(email_confirmed) = LOWER(?)', [trim($key)]);
		if ($user)
			return $user;

		if ($throw)
			throw new SimpleException('Invalid user name "' . $key . '"');

		return null;
	}

	public static function create()
	{
		$user = R::dispense(self::getTableName());
		$user->pass_salt = md5(mt_rand() . uniqid());
		return $user;
	}

	public static function remove($user)
	{
		//remove stuff from auxiliary tables
		R::trashAll(R::find('postscore', 'user_id = ?', [$user->id]));
		foreach ($user->alias('commenter')->ownComment as $comment)
		{
			$comment->commenter = null;
			R::store($comment);
		}
		foreach ($user->alias('uploader')->ownPost as $post)
		{
			$post->uploader = null;
			R::store($post);
		}
		$user->ownFavoritee = [];
		R::store($user);
		R::trash($user);
	}

	public static function save($user)
	{
		R::store($user);
	}



	public static function getAnonymousName()
	{
		return '[Anonymous user]';
	}

	public static function validateUserName($userName)
	{
		$userName = trim($userName);

		$dbUser = R::findOne(self::getTableName(), 'LOWER(name) = LOWER(?)', [$userName]);
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

	public function hasEnabledSafety($safety)
	{
		$all = $this->getSetting(self::SETTING_SAFETY);
		if (!$all)
			return $safety == PostSafety::Safe;
		return $all & PostSafety::toFlag($safety);
	}

	public function enableSafety($safety, $enabled)
	{
		$all = $this->getSetting(self::SETTING_SAFETY);
		if (!$all)
			$all = PostSafety::toFlag(PostSafety::Safe);

		$new = $all;
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

	public function hasEnabledPostTagTitles()
	{
		$ret = $this->getSetting(self::SETTING_POST_TAG_TITLES);
		if ($ret === null)
			$ret = \Chibi\Registry::getConfig()->browsing->showPostTagTitlesDefault;
		return $ret;
	}

	public function enablePostTagTitles($enabled)
	{
		$this->setSetting(self::SETTING_POST_TAG_TITLES, $enabled ? 1 : 0);
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

	public function hasFavorited($post)
	{
		foreach ($this->bean->ownFavoritee as $fav)
			if ($fav->post->id == $post->id)
				return true;
		return false;
	}

	public function getScore($post)
	{
		$s = R::findOne('postscore', 'post_id = ? AND user_id = ?', [$post->id, $this->id]);
		if ($s)
			return intval($s->score);
		return null;
	}

	public function addToFavorites($post)
	{
		R::preload($this->bean, ['favoritee' => 'post']);
		foreach ($this->bean->ownFavoritee as $fav)
			if ($fav->post_id == $post->id)
				throw new SimpleException('Already in favorites');

		$this->bean->link('favoritee')->post = $post;
	}

	public function remFromFavorites($post)
	{
		$finalKey = null;
		foreach ($this->bean->ownFavoritee as $key => $fav)
			if ($fav->post_id == $post->id)
				$finalKey = $key;

		if ($finalKey === null)
			throw new SimpleException('Not in favorites');

		unset($this->bean->ownFavoritee[$finalKey]);
	}

	public function score($post, $score)
	{
		R::trashAll(R::find('postscore', 'post_id = ? AND user_id = ?', [$post->id, $this->id]));
		$score = intval($score);
		if ($score != 0)
		{
			$p = $this->bean->link('postscore');
			$p->post = $post;
			$p->score = $score;
		}
	}
}
