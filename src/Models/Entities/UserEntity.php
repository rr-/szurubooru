<?php
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
			$ret = !\Chibi\Registry::getConfig()->browsing->showDislikedPostsDefault;
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
			$ret = \Chibi\Registry::getConfig()->browsing->showPostTagTitlesDefault;
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
			$ret = \Chibi\Registry::getConfig()->browsing->endlessScrollingDefault;
		return $ret;
	}

	public function enableEndlessScrolling($enabled)
	{
		$this->setSetting(UserModel::SETTING_ENDLESS_SCROLLING, $enabled ? 1 : 0);
	}

	public function hasFavorited($post)
	{
		$query = (new SqlQuery)
			->select('count(1)')->as('count')
			->from('favoritee')
			->where('user_id = ?')->put($this->id)
			->and('post_id = ?')->put($post->id);
		return Database::fetchOne($query)['count'] == 1;
	}

	public function getScore($post)
	{
		$query = (new SqlQuery)
			->select('score')
			->from('post_score')
			->where('user_id = ?')->put($this->id)
			->and('post_id = ?')->put($post->id);
		$row = Database::fetchOne($query);
		if ($row)
			return intval($row['score']);
		return null;
	}

	public function getFavoriteCount()
	{
		$sqlQuery = (new SqlQuery)
			->select('count(1)')->as('count')
			->from('favoritee')
			->where('user_id = ?')->put($this->id);
		return Database::fetchOne($sqlQuery)['count'];
	}

	public function getCommentCount()
	{
		$sqlQuery = (new SqlQuery)
			->select('count(1)')->as('count')
			->from('comment')
			->where('commenter_id = ?')->put($this->id);
		return Database::fetchOne($sqlQuery)['count'];
	}

	public function getPostCount()
	{
		$sqlQuery = (new SqlQuery)
			->select('count(1)')->as('count')
			->from('post')
			->where('uploader_id = ?')->put($this->id);
		return Database::fetchOne($sqlQuery)['count'];
	}
}
