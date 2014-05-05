<?php
use \Chibi\Sql as Sql;

class PostSearchParser extends AbstractSearchParser
{
	private $tags;
	private $showHidden = false;
	private $showDisliked = false;

	protected function processSetup(&$tokens)
	{
		$config = getConfig();

		$this->tags = [];
		$crit = new Sql\ConjunctionFunctor();

		$allowedSafety = array_map(
			function($safety)
			{
				return $safety->toInteger();
			},
			Access::getAllowedSafety());
		$crit->add(Sql\InFunctor::fromArray('post.safety', Sql\Binding::fromArray($allowedSafety)));

		$this->statement->setCriterion($crit);
		if (count($tokens) > $config->browsing->maxSearchTokens)
			throw new SimpleException('Too many search tokens (maximum: %d)', $config->browsing->maxSearchTokens);
	}

	protected function processTeardown()
	{
		if (Auth::getCurrentUser()->hasEnabledHidingDislikedPosts() and !$this->showDisliked)
			$this->processComplexToken('special', 'disliked', true);

		if (!Access::check(new Privilege(Privilege::ListPosts, 'hidden')) or !$this->showHidden)
			$this->processComplexToken('special', 'hidden', true);

		foreach ($this->tags as $item)
		{
			list ($tagName, $neg) = $item;
			$tag = TagModel::findByName($tagName);
			$innerStmt = new Sql\SelectStatement();
			$innerStmt->setTable('post_tag');
			$innerStmt->setCriterion((new Sql\ConjunctionFunctor)
				->add(new Sql\EqualsFunctor('post_tag.post_id', 'post.id'))
				->add(new Sql\EqualsFunctor('post_tag.tag_id', new Sql\Binding($tag->id))));
			$operator = new Sql\ExistsFunctor($innerStmt);
			if ($neg)
				$operator = new Sql\NegationFunctor($operator);
			$this->statement->getCriterion()->add($operator);
		}

		$this->statement->addOrderBy('post.id',
			empty($this->statement->getOrderBy())
				? Sql\SelectStatement::ORDER_DESC
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
			return Sql\InFunctor::fromArray('post.id', Sql\Binding::fromArray($ids));
		}

		elseif (in_array($key, ['fav', 'favs', 'favd']))
		{
			$user = UserModel::findByNameOrEmail($value);
			$innerStmt = (new Sql\SelectStatement)
				->setTable('favoritee')
				->setCriterion((new Sql\ConjunctionFunctor)
					->add(new Sql\EqualsFunctor('favoritee.post_id', 'post.id'))
					->add(new Sql\EqualsFunctor('favoritee.user_id', new Sql\Binding($user->id))));
			return new Sql\ExistsFunctor($innerStmt);
		}

		elseif (in_array($key, ['comment', 'comments', 'commenter', 'commented']))
		{
			$user = UserModel::findByNameOrEmail($value);
			$innerStmt = (new Sql\SelectStatement)
				->setTable('comment')
				->setCriterion((new Sql\ConjunctionFunctor)
					->add(new Sql\EqualsFunctor('comment.post_id', 'post.id'))
					->add(new Sql\EqualsFunctor('comment.commenter_id', new Sql\Binding($user->id))));
			return new Sql\ExistsFunctor($innerStmt);
		}

		elseif (in_array($key, ['submit', 'upload', 'uploads', 'uploader', 'uploaded']))
		{
			$user = UserModel::findByNameOrEmail($value);
			return new Sql\EqualsFunctor('post.uploader_id', new Sql\Binding($user->id));
		}

		elseif (in_array($key, ['idmin', 'id_min']))
			return new Sql\EqualsOrGreaterFunctor('post.id', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['idmax', 'id_max']))
			return new Sql\EqualsOrLesserFunctor('post.id', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['scoremin', 'score_min']))
			return new Sql\EqualsOrGreaterFunctor('post.score', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['scoremax', 'score_max']))
			return new Sql\EqualsOrLesserFunctor('post.score', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['tagmin', 'tag_min']))
			return new Sql\EqualsOrGreaterFunctor('post.tag_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['tagmax', 'tag_max']))
			return new Sql\EqualsOrLesserFunctor('post.tag_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['favmin', 'fav_min']))
			return new Sql\EqualsOrGreaterFunctor('post.fav_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['favmax', 'fav_max']))
			return new Sql\EqualsOrLesserFunctor('post.fav_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['commentmin', 'comment_min']))
			return new Sql\EqualsOrGreaterFunctor('post.comment_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['commentmax', 'comment_max']))
			return new Sql\EqualsOrLesserFunctor('post.comment_count', new Sql\Binding(intval($value)));

		elseif (in_array($key, ['date']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return (new Sql\ConjunctionFunctor)
				->add(new Sql\EqualsOrLesserFunctor('post.upload_date', new Sql\Binding($dateMax)))
				->add(new Sql\EqualsOrGreaterFunctor('post.upload_date', new Sql\Binding($dateMin)));
		}

		elseif (in_array($key, ['datemin', 'date_min']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return new Sql\EqualsOrGreaterFunctor('post.upload_date', new Sql\Binding($dateMin));
		}

		elseif (in_array($key, ['datemax', 'date_max']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return new Sql\EqualsOrLesserFunctor('post.upload_date', new Sql\Binding($dateMax));
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
					$this->statement->addLeftOuterJoin('post_score', (new Sql\ConjunctionFunctor)
						->add(new Sql\EqualsFunctor('post_score.post_id', 'post.id'))
						->add(new Sql\EqualsFunctor('post_score.user_id', new Sql\Binding($activeUser->id))));
				}
				return new Sql\EqualsFunctor(new Sql\IfNullFunctor('post_score.score', '0'), '1');
			}

			elseif (in_array($value, ['dislike', 'disliked', 'dislikes']))
			{
				$this->showDisliked = true;
				if (!$this->statement->isTableJoined('post_score'))
				{
					$this->statement->addLeftOuterJoin('post_score', (new Sql\ConjunctionFunctor)
						->add(new Sql\EqualsFunctor('post_score.post_id', 'post.id'))
						->add(new Sql\EqualsFunctor('post_score.user_id', new Sql\Binding($activeUser->id))));
				}
				return new Sql\EqualsFunctor(new Sql\IfNullFunctor('post_score.score', '0'), '-1');
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
			if ($value == 'swf')
				$type = PostType::Flash;
			elseif ($value == 'img')
				$type = PostType::Image;
			elseif ($value == 'video' or in_array($value, ['mp4', 'webm', 'ogg', '3gp', 'ogg']))
				$type = PostType::Video;
			elseif ($value == 'yt' or $value == 'youtube')
				$type = PostType::Youtube;
			else
				throw new SimpleException('Invalid post type "%s"', $value);

			return new Sql\EqualsFunctor('type', new Sql\Binding($type));
		}

		return null;
	}

	protected function processComplexToken($key, $value, $neg)
	{
		$criterion = $this->prepareCriterionForComplexToken($key, $value);
		if (!$criterion)
			return false;

		if ($neg)
			$criterion = new Sql\NegationFunctor($criterion);

		$this->statement->getCriterion()->add($criterion);
		return true;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		$randomReset = true;

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
			$randomReset = false;
			if (!isset($_SESSION['browsing-seed']))
				$_SESSION['browsing-seed'] = mt_rand();
			$seed = $_SESSION['browsing-seed'];
			$orderColumn = new Sql\SubstrFunctor(
				new Sql\MultiplicationFunctor('post.id', $seed),
				new Sql\AdditionFunctor(new Sql\LengthFunctor('post.id'), '2'));
		}

		else
			return false;

		if ($randomReset and isset($_SESSION['browsing-seed']))
			unset($_SESSION['browsing-seed']);

		$this->statement->setOrderBy($orderColumn, $orderDir);
		return true;
	}

	protected static function parseDate($value)
	{
		list ($year, $month, $day) = explode('-', $value . '-0-0');
		$year = intval($year);
		$month = intval($month);
		$day = intval($day);
		$yearMin = $yearMax = $year;
		$monthMin = $monthMax = $month;
		$monthMin = $monthMin ?: 1;
		$monthMax = $monthMax ?: 12;
		$dayMin = $dayMax = $day;
		$dayMin = $dayMin ?: 1;
		$dayMax = $dayMax ?: intval(date('t', mktime(0, 0, 0, $monthMax, 1, $year)));
		$timeMin = mktime(0, 0, 0, $monthMin, $dayMin, $yearMin);
		$timeMax = mktime(0, 0, -1, $monthMax, $dayMax+1, $yearMax);
		return [$timeMin, $timeMax];
	}
}
