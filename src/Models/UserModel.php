<?php
class UserModel extends AbstractCrudModel
{
	const SETTING_SAFETY = 1;
	const SETTING_ENDLESS_SCROLLING = 2;
	const SETTING_POST_TAG_TITLES = 3;
	const SETTING_HIDE_DISLIKED_POSTS = 4;

	public static function getTableName()
	{
		return 'user';
	}

	public static function spawn()
	{
		$user = new UserEntity();
		$user->passSalt = md5(mt_rand() . uniqid());
		return $user;
	}

	public static function save($user)
	{
		if ($user->accessRank == AccessRank::Anonymous)
			throw new Exception('Trying to save anonymous user into database');
		Database::transaction(function() use ($user)
		{
			self::forgeId($user);

			$bindings = [
				'name' => $user->name,
				'pass_salt' => $user->passSalt,
				'pass_hash' => $user->passHash,
				'staff_confirmed' => $user->staffConfirmed,
				'email_unconfirmed' => $user->emailUnconfirmed,
				'email_confirmed' => $user->emailConfirmed,
				'join_date' => $user->joinDate,
				'access_rank' => $user->accessRank,
				'settings' => $user->settings,
				'banned' => $user->banned
				];

			$query = (new SqlQuery)
				->update('user')
				->set(join(', ', array_map(function($key) { return $key . ' = ?'; }, array_keys($bindings))))
				->put(array_values($bindings))
				->where('id = ?')->put($user->id);
			Database::query($query);
		});
	}

	public static function remove($user)
	{
		Database::transaction(function() use ($user)
		{
			$queries = [];

			$queries []= (new SqlQuery)
				->deleteFrom('post_score')
				->where('user_id = ?')->put($user->id);

			$queries []= (new SqlQuery)
				->update('comment')
				->set('commenter_id = NULL')
				->where('commenter_id = ?')->put($user->id);

			$queries []= (new SqlQuery)
				->update('post')
				->set('uploader_id = NULL')
				->where('uploader_id = ?')->put($user->id);

			$queries []= (new SqlQuery)
				->deleteFrom('favoritee')
				->where('user_id = ?')->put($user->id);

			$queries []= (new SqlQuery)
				->deleteFrom('user')
				->where('id = ?')->put($user->id);

			foreach ($queries as $query)
				Database::query($query);
		});
	}



	public static function findByName($key, $throw = true)
	{
		$query = (new SqlQuery)
			->select('*')
			->from('user')
			->where('LOWER(name) = LOWER(?)')->put(trim($key));

		$row = Database::fetchOne($query);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid user name "' . $key . '"');
		return null;
	}

	public static function findByNameOrEmail($key, $throw = true)
	{
		$query = new SqlQuery();
		$query->select('*')
			->from('user')
			->where('LOWER(name) = LOWER(?)')->put(trim($key))
			->or('LOWER(email_confirmed) = LOWER(?)')->put(trim($key));

		$row = Database::fetchOne($query);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid user name "' . $key . '"');
		return null;
	}



	public static function updateUserScore($user, $post, $score)
	{
		Database::transaction(function() use ($user, $post, $score)
		{
			$query = (new SqlQuery)
				->deleteFrom('post_score')
				->where('post_id = ?')->put($post->id)
				->and('user_id = ?')->put($user->id);
			Database::query($query);
			$score = intval($score);
			if ($score != 0)
			{
				$query = (new SqlQuery);
				$query->insertInto('post_score')
					->surround('post_id, user_id, score')
					->values()->surround('?, ?, ?')
					->put([$post->id, $user->id, $score]);
				Database::query($query);
			}
		});
	}

	public static function addToUserFavorites($user, $post)
	{
		Database::transaction(function() use ($user, $post)
		{
			self::removeFromUserFavorites($user, $post);
			$query = (new SqlQuery);
			$query->insertInto('favoritee')
				->surround('post_id, user_id')
				->values()->surround('?, ?')
				->put([$post->id, $user->id]);
			Database::query($query);
		});
	}

	public static function removeFromUserFavorites($user, $post)
	{
		Database::transaction(function() use ($user, $post)
		{
			$query = (new SqlQuery)
				->deleteFrom('favoritee')
				->where('post_id = ?')->put($post->id)
				->and('user_id = ?')->put($user->id);
			Database::query($query);
		});
	}



	public static function validateUserName($userName)
	{
		$userName = trim($userName);

		$dbUser = self::findByName($userName, false);
		if ($dbUser !== null)
		{
			if (!$dbUser->emailConfirmed and \Chibi\Registry::getConfig()->registration->needEmailForRegistering)
				throw new SimpleException('User with this name is already registered and awaits e-mail confirmation');

			if (!$dbUser->staffConfirmed and \Chibi\Registry::getConfig()->registration->staffActivation)
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



	public static function getAnonymousName()
	{
		return '[Anonymous user]';
	}

	public static function hashPassword($pass, $salt2)
	{
		$salt1 = \Chibi\Registry::getConfig()->main->salt;
		return sha1($salt1 . $salt2 . $pass);
	}
}
