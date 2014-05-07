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

	public static function convertRow($row)
	{
		$entity = parent::convertRow($row);

		if (isset($row['access_rank']))
			$entity->setAccessRank(new AccessRank($row['access_rank']));

		return $entity;
	}

	public static function spawn()
	{
		$user = new UserEntity();
		$user->setAccessRank(new AccessRank(AccessRank::Anonymous));
		$user->setPasswordSalt(md5(mt_rand() . uniqid()));
		return $user;
	}

	public static function save($user)
	{
		$user->validate();

		Database::transaction(function() use ($user)
		{
			self::forgeId($user);

			$bindings = [
				'name' => $user->getName(),
				'pass_salt' => $user->getPasswordSalt(),
				'pass_hash' => $user->getPasswordHash(),
				'staff_confirmed' => $user->staffConfirmed,
				'email_unconfirmed' => $user->getUnconfirmedEmail(),
				'email_confirmed' => $user->getConfirmedEmail(),
				'join_date' => $user->joinDate,
				'last_login_date' => $user->lastLoginDate,
				'access_rank' => $user->getAccessRank()->toInteger(),
				'settings' => $user->settings,
				'banned' => $user->isBanned(),
				];

			$stmt = (new Sql\UpdateStatement)
				->setTable('user')
				->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($user->getId())));

			foreach ($bindings as $key => $val)
				$stmt->setColumn($key, new Sql\Binding($val));

			Database::exec($stmt);
		});

		return $user;
	}

	public static function remove($user)
	{
		Database::transaction(function() use ($user)
		{
			$binding = new Sql\Binding($user->getId());

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
			throw new SimpleNotFoundException('Invalid user name "%s"', $key);
		return null;
	}

	public static function findByNameOrEmail($key, $throw = true)
	{
		$key = trim($key);

		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable('user');
		$stmt->setCriterion((new Sql\DisjunctionFunctor)
			->add(new Sql\NoCaseFunctor(new Sql\EqualsFunctor('name', new Sql\Binding($key))))
			->add(new Sql\NoCaseFunctor(new Sql\EqualsFunctor('email_unconfirmed', new Sql\Binding($key))))
			->add(new Sql\NoCaseFunctor(new Sql\EqualsFunctor('email_confirmed', new Sql\Binding($key)))));

		$row = Database::fetchOne($stmt);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid user name "%s"', $key);
		return null;
	}



	public static function updateUserScore($user, $post, $score)
	{
		Database::transaction(function() use ($user, $post, $score)
		{
			$stmt = new Sql\DeleteStatement();
			$stmt->setTable('post_score');
			$stmt->setCriterion((new Sql\ConjunctionFunctor)
				->add(new Sql\EqualsFunctor('post_id', new Sql\Binding($post->getId())))
				->add(new Sql\EqualsFunctor('user_id', new Sql\Binding($user->getId()))));
			Database::exec($stmt);
			$score = intval($score);
			if ($score != 0)
			{
				$stmt = new Sql\InsertStatement();
				$stmt->setTable('post_score');
				$stmt->setColumn('post_id', new Sql\Binding($post->getId()));
				$stmt->setColumn('user_id', new Sql\Binding($user->getId()));
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
			$stmt->setColumn('post_id', new Sql\Binding($post->getId()));
			$stmt->setColumn('user_id', new Sql\Binding($user->getId()));
			$stmt->setColumn('fav_date', time());
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
				->add(new Sql\EqualsFunctor('post_id', new Sql\Binding($post->getId())))
				->add(new Sql\EqualsFunctor('user_id', new Sql\Binding($user->getId()))));
			Database::exec($stmt);
		});
	}

	public static function validateEmail($email)
	{
		$email = trim($email);

		if (!empty($email) and !TextHelper::isValidEmail($email))
			throw new SimpleException('E-mail address appears to be invalid');

		return $email;
	}


	public static function getAnonymousName()
	{
		return '[Anonymous user]';
	}

	public static function hashPassword($pass, $salt2)
	{
		$salt1 = getConfig()->main->salt;
		return sha1($salt1 . $salt2 . $pass);
	}
}
