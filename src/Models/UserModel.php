<?php
use \Chibi\Sql as Sql;

final class UserModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'user';
	}

	protected static function saveSingle($user)
	{
		$user->validate();

		Core::getDatabase()->transaction(function() use ($user)
		{
			self::forgeId($user);

			$bindings = [
				'name' => $user->getName(),
				'pass_salt' => $user->getPasswordSalt(),
				'pass_hash' => $user->getPasswordHash(),
				'staff_confirmed' => $user->isStaffConfirmed() ? 1 : 0,
				'email_unconfirmed' => $user->getUnconfirmedEmail(),
				'email_confirmed' => $user->getConfirmedEmail(),
				'join_date' => $user->getJoinTime(),
				'last_login_date' => $user->getLastLoginTime(),
				'access_rank' => $user->getAccessRank()->toInteger(),
				'settings' => $user->getSettings()->getAllAsSerializedString(),
				'banned' => $user->isBanned() ? 1 : 0,
				'avatar_style' => $user->getAvatarStyle()->toInteger(),
				];

			$stmt = Sql\Statements::update()
				->setTable('user')
				->setCriterion(Sql\Functors::equals('id', new Sql\Binding($user->getId())));

			foreach ($bindings as $key => $val)
				$stmt->setColumn($key, new Sql\Binding($val));

			Core::getDatabase()->execute($stmt);
		});

		return $user;
	}

	protected static function removeSingle($user)
	{
		Core::getDatabase()->transaction(function() use ($user)
		{
			$binding = new Sql\Binding($user->getId());

			$stmt = Sql\Statements::delete();
			$stmt->setTable('post_score');
			$stmt->setCriterion(Sql\Functors::equals('user_id', $binding));
			Core::getDatabase()->execute($stmt);

			$stmt->setTable('favoritee');
			Core::getDatabase()->execute($stmt);

			$stmt->setTable('user');
			$stmt->setCriterion(Sql\Functors::equals('id', $binding));
			Core::getDatabase()->execute($stmt);

			$stmt = Sql\Statements::update();
			$stmt->setTable('comment');
			$stmt->setCriterion(Sql\Functors::equals('commenter_id', $binding));
			$stmt->setColumn('commenter_id', Sql\Functors::null());
			Core::getDatabase()->execute($stmt);

			$stmt = Sql\Statements::update();
			$stmt->setTable('post');
			$stmt->setCriterion(Sql\Functors::equals('uploader_id', $binding));
			$stmt->setColumn('uploader_id', Sql\Functors::null());
			Core::getDatabase()->execute($stmt);
		});
	}



	public static function getByName($key)
	{
		$ret = self::tryGetByName($key);
		if (!$ret)
			throw new SimpleNotFoundException('Invalid user name "%s"', $key);
		return $ret;
	}

	public static function tryGetByName($key)
	{
		$stmt = Sql\Statements::select();
		$stmt->setColumn('*');
		$stmt->setTable('user');
		$stmt->setCriterion(Sql\Functors::noCase(Sql\Functors::equals('name', new Sql\Binding(trim($key)))));

		$row = Core::getDatabase()->fetchOne($stmt);
		return self::spawnFromDatabaseRow($row);
	}

	public static function getByEmail($key)
	{
		$ret = self::tryGetByEmail($key);
		if (!$ret)
			throw new SimpleNotFoundException('Invalid user e-mail "%s"', $key);
		return $ret;
	}

	public static function tryGetByEmail($key)
	{
		$key = trim($key);

		$stmt = Sql\Statements::select();
		$stmt->setColumn('*');
		$stmt->setTable('user');
		$stmt->setCriterion(Sql\Functors::disjunction()
			->add(Sql\Functors::noCase(Sql\Functors::equals('email_unconfirmed', new Sql\Binding($key))))
			->add(Sql\Functors::noCase(Sql\Functors::equals('email_confirmed', new Sql\Binding($key)))));

		$row = Core::getDatabase()->fetchOne($stmt);
		return self::spawnFromDatabaseRow($row);
	}



	public static function updateUserScore($user, $post, $score)
	{
		Core::getDatabase()->transaction(function() use ($user, $post, $score)
		{
			$post->removeCache('score');
			$stmt = Sql\Statements::delete();
			$stmt->setTable('post_score');
			$stmt->setCriterion(Sql\Functors::conjunction()
				->add(Sql\Functors::equals('post_id', new Sql\Binding($post->getId())))
				->add(Sql\Functors::equals('user_id', new Sql\Binding($user->getId()))));
			Core::getDatabase()->execute($stmt);
			$score = intval($score);
			if (abs($score) > 1)
				throw new SimpleException('Invalid score');
			if ($score != 0)
			{
				$stmt = Sql\Statements::insert();
				$stmt->setTable('post_score');
				$stmt->setColumn('post_id', new Sql\Binding($post->getId()));
				$stmt->setColumn('user_id', new Sql\Binding($user->getId()));
				$stmt->setColumn('score', new Sql\Binding($score));
				Core::getDatabase()->execute($stmt);
			}
		});
	}

	public static function addToUserFavorites($user, $post)
	{
		Core::getDatabase()->transaction(function() use ($user, $post)
		{
			$post->removeCache('fav_count');
			self::removeFromUserFavorites($user, $post);
			$stmt = Sql\Statements::insert();
			$stmt->setTable('favoritee');
			$stmt->setColumn('post_id', new Sql\Binding($post->getId()));
			$stmt->setColumn('user_id', new Sql\Binding($user->getId()));
			$stmt->setColumn('fav_date', time());
			Core::getDatabase()->execute($stmt);
		});
	}

	public static function removeFromUserFavorites($user, $post)
	{
		Core::getDatabase()->transaction(function() use ($user, $post)
		{
			$post->removeCache('fav_count');
			$stmt = Sql\Statements::delete();
			$stmt->setTable('favoritee');
			$stmt->setCriterion(Sql\Functors::conjunction()
				->add(Sql\Functors::equals('post_id', new Sql\Binding($post->getId())))
				->add(Sql\Functors::equals('user_id', new Sql\Binding($user->getId()))));
			Core::getDatabase()->execute($stmt);
		});
	}

	public static function getAnonymousName()
	{
		return '[Anonymous user]';
	}

	public static function hashPassword($pass, $salt2)
	{
		$salt1 = Core::getConfig()->main->salt;
		return sha1($salt1 . $salt2 . $pass);
	}
}
