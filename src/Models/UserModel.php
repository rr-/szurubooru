<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

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
				'last_login_date' => $user->lastLoginDate,
				'access_rank' => $user->accessRank,
				'settings' => $user->settings,
				'banned' => $user->banned
				];

			$stmt = (new Sql\UpdateStatement)
				->setTable('user')
				->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($user->id)));

			foreach ($bindings as $key => $val)
				$stmt->setColumn($key, new Sql\Binding($val));

			Database::exec($stmt);
		});
	}

	public static function remove($user)
	{
		Database::transaction(function() use ($user)
		{
			$binding = new Sql\Binding($user->id);

			$stmt = new Sql\DeleteStatement();
			$stmt->setTable('post_score');
			$stmt->setCriterion(new Sql\EqualsFunctor('user_id', $binding));
			Database::exec($stmt);

			$stmt->setTable('favoritee');
			Database::exec($stmt);

			$stmt->setTable('user');
			$stmt->setCriterion(new Sql\EqualsFunctor('id', $binding));
			Database::exec($stmt);

			$stmt = new Sql\UpdateStatement();
			$stmt->setTable('comment');
			$stmt->setCriterion(new Sql\EqualsFunctor('commenter_id', $binding));
			$stmt->setColumn('commenter_id', new Sql\NullFunctor());
			Database::exec($stmt);

			$stmt = new Sql\UpdateStatement();
			$stmt->setTable('post');
			$stmt->setCriterion(new Sql\EqualsFunctor('uploader_id', $binding));
			$stmt->setColumn('uploader_id', new Sql\NullFunctor());
			Database::exec($stmt);
		});
	}



	public static function findByName($key, $throw = true)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable('user');
		$stmt->setCriterion(new Sql\NoCaseFunctor(new Sql\EqualsFunctor('name', new Sql\Binding(trim($key)))));

		$row = Database::fetchOne($stmt);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid user name "' . $key . '"');
		return null;
	}

	public static function findByNameOrEmail($key, $throw = true)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable('user');
		$stmt->setCriterion((new Sql\DisjunctionFunctor)
			->add(new Sql\NoCaseFunctor(new Sql\EqualsFunctor('name', new Sql\Binding(trim($key)))))
			->add(new Sql\NoCaseFunctor(new Sql\EqualsFunctor('email_confirmed', new Sql\Binding(trim($key))))));

		$row = Database::fetchOne($stmt);
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
			$stmt = new Sql\DeleteStatement();
			$stmt->setTable('post_score');
			$stmt->setCriterion((new Sql\ConjunctionFunctor)
				->add(new Sql\EqualsFunctor('post_id', new Sql\Binding($post->id)))
				->add(new Sql\EqualsFunctor('user_id', new Sql\Binding($user->id))));
			Database::exec($stmt);
			$score = intval($score);
			if ($score != 0)
			{
				$stmt = new Sql\InsertStatement();
				$stmt->setTable('post_score');
				$stmt->setColumn('post_id', new Sql\Binding($post->id));
				$stmt->setColumn('user_id', new Sql\Binding($user->id));
				$stmt->setColumn('score', new Sql\Binding($score));
				Database::exec($stmt);
			}
		});
	}

	public static function addToUserFavorites($user, $post)
	{
		Database::transaction(function() use ($user, $post)
		{
			self::removeFromUserFavorites($user, $post);
			$stmt = new Sql\InsertStatement();
			$stmt->setTable('favoritee');
			$stmt->setColumn('post_id', new Sql\Binding($post->id));
			$stmt->setColumn('user_id', new Sql\Binding($user->id));
			Database::exec($stmt);
		});
	}

	public static function removeFromUserFavorites($user, $post)
	{
		Database::transaction(function() use ($user, $post)
		{
			$stmt = new Sql\DeleteStatement();
			$stmt->setTable('favoritee');
			$stmt->setCriterion((new Sql\ConjunctionFunctor)
				->add(new Sql\EqualsFunctor('post_id', new Sql\Binding($post->id)))
				->add(new Sql\EqualsFunctor('user_id', new Sql\Binding($user->id))));
			Database::exec($stmt);
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
