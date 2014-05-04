<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class UserEntity extends AbstractEntity
{
	public $name;
	public $passSalt;
	public $passHash;
	public $staffConfirmed;
	public $emailUnconfirmed;
	public $emailConfirmed;
	public $joinDate;
	public $lastLoginDate;
	public $accessRank;
	public $settings;
	public $banned;

	public function getAvatarUrl($size = 32)
	{
		$subject = !empty($this->emailConfirmed)
			? $this->emailConfirmed
			: $this->passSalt . $this->name;
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
		$all = $this->getSetting(UserModel::SETTING_SAFETY);
		if (!$all)
			return $safety == PostSafety::Safe;
		return $all & PostSafety::toFlag($safety);
	}

	public function enableSafety($safety, $enabled)
	{
		$all = $this->getSetting(UserModel::SETTING_SAFETY);
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

		$this->setSetting(UserModel::SETTING_SAFETY, $new);
	}

	public function hasEnabledHidingDislikedPosts()
	{
		$ret = $this->getSetting(UserModel::SETTING_HIDE_DISLIKED_POSTS);
		if ($ret === null)
			$ret = !getConfig()->browsing->showDislikedPostsDefault;
		return $ret;
	}

	public function enableHidingDislikedPosts($enabled)
	{
		$this->setSetting(UserModel::SETTING_HIDE_DISLIKED_POSTS, $enabled ? 1 : 0);
	}

	public function hasEnabledPostTagTitles()
	{
		$ret = $this->getSetting(UserModel::SETTING_POST_TAG_TITLES);
		if ($ret === null)
			$ret = getConfig()->browsing->showPostTagTitlesDefault;
		return $ret;
	}

	public function enablePostTagTitles($enabled)
	{
		$this->setSetting(UserModel::SETTING_POST_TAG_TITLES, $enabled ? 1 : 0);
	}

	public function hasEnabledEndlessScrolling()
	{
		$ret = $this->getSetting(UserModel::SETTING_ENDLESS_SCROLLING);
		if ($ret === null)
			$ret = getConfig()->browsing->endlessScrollingDefault;
		return $ret;
	}

	public function enableEndlessScrolling($enabled)
	{
		$this->setSetting(UserModel::SETTING_ENDLESS_SCROLLING, $enabled ? 1 : 0);
	}

	public function confirmEmail()
	{
		if (!empty($this->emailConfirmed))
			return;

		$this->emailConfirmed = $user->emailUnconfirmed;
		$this->emailUnconfirmed = null;
	}

	public function hasFavorited($post)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('favoritee');
		$stmt->setCriterion((new Sql\ConjunctionFunctor)
			->add(new Sql\EqualsFunctor('user_id', new Sql\Binding($this->id)))
			->add(new Sql\EqualsFunctor('post_id', new Sql\Binding($post->id))));
		return Database::fetchOne($stmt)['count'] == 1;
	}

	public function getScore($post)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('score');
		$stmt->setTable('post_score');
		$stmt->setCriterion((new Sql\ConjunctionFunctor)
			->add(new Sql\EqualsFunctor('user_id', new Sql\Binding($this->id)))
			->add(new Sql\EqualsFunctor('post_id', new Sql\Binding($post->id))));
		$row = Database::fetchOne($stmt);
		if ($row)
			return intval($row['score']);
		return null;
	}

	public function getFavoriteCount()
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('favoritee');
		$stmt->setCriterion(new Sql\EqualsFunctor('user_id', new Sql\Binding($this->id)));
		return Database::fetchOne($stmt)['count'];
	}

	public function getCommentCount()
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('comment');
		$stmt->setCriterion(new Sql\EqualsFunctor('commenter_id', new Sql\Binding($this->id)));
		return Database::fetchOne($stmt)['count'];
	}

	public function getPostCount()
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('post');
		$stmt->setCriterion(new Sql\EqualsFunctor('uploader_id', new Sql\Binding($this->id)));
		return Database::fetchOne($stmt)['count'];
	}
}
