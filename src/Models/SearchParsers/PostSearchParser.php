<?php
use \Chibi\Sql as Sql;

class PostSearchParser extends AbstractSearchParser
{
	private $tags;
	private $showHidden;
	private $showDisliked;
	private $randomReset;

	protected function processSetup(&$tokens)
	{
		$config = Core::getConfig();

		$this->showHidden = false;
		$this->showDisliked = false;
		$this->randomReset = true;
		$this->tags = [];
		$crit = Sql\Functors::conjunction();

		$allowedSafety = array_map(
			function($safety)
			{
				return $safety->toInteger();
			},
			Access::getAllowedSafety());
		$crit->add(Sql\Functors::in('post.safety', Sql\Binding::fromArray($allowedSafety)));

		$this->statement->setCriterion($crit);
		if (count($tokens) > $config->browsing->maxSearchTokens)
			throw new SimpleException('Too many search tokens (maximum: %d)', $config->browsing->maxSearchTokens);
	}

	protected function processTeardown()
	{
		if (Auth::getCurrentUser()->getSettings()->hasEnabledHidingDislikedPosts() and !$this->showDisliked)
			$this->processComplexToken('special', 'disliked', true);

		if (!Access::check(new Privilege(Privilege::ListPosts, 'hidden')) or !$this->showHidden)
			$this->processComplexToken('special', 'hidden', true);

		if ($this->randomReset and isset($_SESSION['browsing-seed']))
			unset($_SESSION['browsing-seed']);

		foreach ($this->tags as $item)
		{
			list ($tagName, $neg) = $item;
			$tag = TagModel::getByName($tagName);
			$innerStmt = Sql\Statements::select();
			$innerStmt->setTable('post_tag');
			$innerStmt->setCriterion(Sql\Functors::conjunction()
				->add(Sql\Functors::equals('post_tag.post_id', 'post.id'))
				->add(Sql\Functors::equals('post_tag.tag_id', new Sql\Binding($tag->getId()))));
			$operator = Sql\Functors::exists($innerStmt);
			if ($neg)
				$operator = Sql\Functors::negation($operator);
			$this->statement->getCriterion()->add($operator);
		}

		$this->statement->addOrderBy('post.id',
			empty($this->statement->getOrderBy())
				? Sql\Statements\SelectStatement::ORDER_DESC
				: $this->statement->getOrderBy()[0][1]);
	}

	protected function processSimpleToken($value, $neg)
	{
		$this->tags []= [$value, $neg];
		return true;
	}

	protected function prepareCriterionForComplexToken($key, $value)
	{
		if (in_array($key, ['id', 'ids']))
		{
			$ids = preg_split('/[;,]/', $value);
			$ids = array_map('intval', $ids);
			return Sql\Functors::in('post.id', Sql\Binding::fromArray($ids));
		}

		if (in_array($key, ['name', 'names', 'hash', 'hashes']))
		{
			$ids = preg_split('/[;,]/', $value);
			return Sql\Functors::in('post.name', Sql\Binding::fromArray($ids));
		}

		elseif (in_array($key, ['fav', 'favs', 'favd']))
		{
			$user = UserModel::getByName($value);
			$innerStmt = Sql\Statements::select()
				->setTable('favoritee')
				->setCriterion(Sql\Functors::conjunction()
					->add(Sql\Functors::equals('favoritee.post_id', 'post.id'))
					->add(Sql\Functors::equals('favoritee.user_id', new Sql\Binding($user->getId()))));
			return Sql\Functors::exists($innerStmt);
		}

		elseif (in_array($key, ['comment', 'comments', 'commenter', 'commented']))
		{
			$user = UserModel::getByName($value);
			$innerStmt = Sql\Statements::select()
				->setTable('comment')
				->setCriterion(Sql\Functors::conjunction()
					->add(Sql\Functors::equals('comment.post_id', 'post.id'))
					->add(Sql\Functors::equals('comment.commenter_id', new Sql\Binding($user->getId()))));
			return Sql\Functors::exists($innerStmt);
		}

		elseif (in_array($key, ['submit', 'upload', 'uploads', 'uploader', 'uploaded']))
		{
			$user = UserModel::getByName($value);
			return Sql\Functors::equals('post.uploader_id', new Sql\Binding($user->getId()));
		}

		elseif (in_array($key, ['idmin', 'id_min']))
			return Sql\Functors::equalsOrGreater('post.id', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['idmax', 'id_max']))
			return Sql\Functors::equalsOrLesser('post.id', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['scoremin', 'score_min']))
			return Sql\Functors::equalsOrGreater('post.score', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['scoremax', 'score_max']))
			return Sql\Functors::equalsOrLesser('post.score', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['tagmin', 'tag_min']))
			return Sql\Functors::equalsOrGreater('post.tag_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['tagmax', 'tag_max']))
			return Sql\Functors::equalsOrLesser('post.tag_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['favmin', 'fav_min']))
			return Sql\Functors::equalsOrGreater('post.fav_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['favmax', 'fav_max']))
			return Sql\Functors::equalsOrLesser('post.fav_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['commentmin', 'comment_min']))
			return Sql\Functors::equalsOrGreater('post.comment_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['commentmax', 'comment_max']))
			return Sql\Functors::equalsOrLesser('post.comment_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['date']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return Sql\Functors::conjunction()
				->add(Sql\Functors::equalsOrLesser('post.upload_date', new Sql\Binding($dateMax)))
				->add(Sql\Functors::equalsOrGreater('post.upload_date', new Sql\Binding($dateMin)));
		}

		elseif (in_array($key, ['datemin', 'date_min']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return Sql\Functors::equalsOrGreater('post.upload_date', new Sql\Binding($dateMin));
		}

		elseif (in_array($key, ['datemax', 'date_max']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return Sql\Functors::equalsOrLesser('post.upload_date', new Sql\Binding($dateMax));
		}

		elseif (in_array($key, ['filesizemin', 'filesize_min']))
		{
			$fileSizeMin = TextHelper::stripBytesUnits($value);
			return Sql\Functors::equalsOrGreater('post.file_size', new Sql\Binding($fileSizeMin));
		}

		elseif (in_array($key, ['filesizemax', 'filesize_max']))
		{
			$fileSizeMax = TextHelper::stripBytesUnits($value);
			return Sql\Functors::equalsOrLesser('post.file_size', new Sql\Binding($fileSizeMax));
		}

		elseif ($key == 'special')
		{
			$activeUser = Auth::getCurrentUser();

			$value = strtolower($value);
			if (in_array($value, ['fav', 'favs', 'favd']))
			{
				return $this->prepareCriterionForComplexToken('fav', $activeUser->getName());
			}

			elseif (in_array($value, ['like', 'liked', 'likes']))
			{
				if (!$this->statement->isTableJoined('post_score'))
				{
					$this->statement->addLeftOuterJoin('post_score', Sql\Functors::conjunction()
						->add(Sql\Functors::equals('post_score.post_id', 'post.id'))
						->add(Sql\Functors::equals('post_score.user_id', new Sql\Binding($activeUser->getId()))));
				}
				return Sql\Functors::equals(Sql\Functors::ifNull('post_score.score', '0'), '1');
			}

			elseif (in_array($value, ['dislike', 'disliked', 'dislikes']))
			{
				$this->showDisliked = true;
				if (!$this->statement->isTableJoined('post_score'))
				{
					$this->statement->addLeftOuterJoin('post_score', Sql\Functors::conjunction()
						->add(Sql\Functors::equals('post_score.post_id', 'post.id'))
						->add(Sql\Functors::equals('post_score.user_id', new Sql\Binding($activeUser->getId()))));
				}
				return Sql\Functors::equals(Sql\Functors::ifNull('post_score.score', '0'), '-1');
			}

			elseif ($value == 'hidden')
			{
				$this->showHidden = true;
				return new Sql\StringExpression('hidden');
			}

			else
				throw new SimpleException('Invalid special token "%s"', $value);
		}

		elseif ($key == 'type')
		{
			$value = strtolower($value);
			if (in_array($value, ['swf', 'flash']))
				$type = PostType::Flash;
			elseif (in_array($value, ['img', 'image']))
				$type = PostType::Image;
			elseif ($value == 'video')
				$type = PostType::Video;
			elseif ($value == 'yt' or $value == 'youtube')
				$type = PostType::Youtube;
			else
				throw new SimpleException('Invalid post type "%s"', $value);

			return Sql\Functors::equals('type', new Sql\Binding($type));
		}

		return null;
	}

	protected function processComplexToken($key, $value, $neg)
	{
		$criterion = $this->prepareCriterionForComplexToken($key, $value);
		if (!$criterion)
			return false;

		if ($neg)
			$criterion = Sql\Functors::negation($criterion);

		$this->statement->getCriterion()->add($criterion);
		return true;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		if (in_array($orderByString, ['id']))
			$orderColumn = 'post.id';

		elseif (in_array($orderByString, ['date']))
			$orderColumn = 'post.upload_date';

		elseif (in_array($orderByString, ['score']))
			$orderColumn = 'post.score';

		elseif (in_array($orderByString, ['comment', 'comments', 'commentcount', 'comment_count']))
			$orderColumn = 'post.comment_count';

		elseif (in_array($orderByString, ['fav', 'favs', 'favcount', 'fav_count']))
			$orderColumn = 'post.fav_count';

		elseif (in_array($orderByString, ['tag', 'tags', 'tagcount', 'tag_count']))
			$orderColumn = 'post.tag_count';

		elseif (in_array($orderByString, ['commentdate', 'comment_date']))
			$orderColumn = 'post.comment_date';

		elseif (in_array($orderByString, ['favdate', 'fav_date']))
			$orderColumn = 'post.fav_date';

		elseif (in_array($orderByString, ['filesize', 'file_size']))
			$orderColumn = 'post.file_size';

		elseif ($orderByString == 'random')
		{
			//seeding works like this: if you visit anything
			//that triggers order other than random, the seed
			//is going to reset. however, it stays the same as
			//long as you keep visiting pages with order:random
			//specified.
			$this->randomReset = false;
			if (!isset($_SESSION['browsing-seed']))
				$_SESSION['browsing-seed'] = mt_rand();
			$seed = $_SESSION['browsing-seed'];
			$orderColumn = Sql\Functors::substr(
				Sql\Functors::multiplication('post.id', $seed),
				Sql\Functors::addition(Sql\Functors::length('post.id'), '2'));
		}

		else
			return false;

		$this->statement->setOrderBy($orderColumn, $orderDir);
		return true;
	}

	protected static function parseDate($value)
	{
		$value = strtolower(trim($value));
		if ($value == 'today')
		{
			$timeMin = mktime(0, 0, 0);
			$timeMax = mktime(24, 0, -1);
		}
		elseif ($value == 'yesterday')
		{
			$timeMin = mktime(-24, 0, 0);
			$timeMax = mktime(0, 0, -1);
		}
		elseif (preg_match('/^(\d{4})$/', $value, $matches))
		{
			$year = intval($matches[1]);
			$timeMin = mktime(0, 0, 0, 1, 1, $year);
			$timeMax = mktime(0, 0, -1, 1, 1, $year + 1);
		}
		elseif (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $matches))
		{
			$year = intval($matches[1]);
			$month = intval($matches[2]);
			$timeMin = mktime(0, 0, 0, $month, 1, $year);
			$timeMax = mktime(0, 0, -1, $month + 1, 1, $year);
		}
		elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $matches))
		{
			$year = intval($matches[1]);
			$month = intval($matches[2]);
			$day = intval($matches[3]);
			$timeMin = mktime(0, 0, 0, $month, $day, $year);
			$timeMax = mktime(0, 0, -1, $month, $day + 1, $year);
		}
		else
			throw new SimpleException('Invalid date format: ' . $value);

		return [$timeMin, $timeMax];
	}
}
