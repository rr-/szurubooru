<?php
class PostSearchParser extends AbstractSearchParser
{
	private $tags;

	protected function processSetup(&$tokens)
	{
		$config = \Chibi\Registry::getConfig();

		$this->tags = [];
		$this->statement->setCriterion(new SqlConjunctionFunctor());

		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$this->statement->getCriterion()->add(SqlInFunctor::fromArray('safety', SqlBinding::fromArray($allowedSafety)));

		if (\Chibi\Registry::getContext()->user->hasEnabledHidingDislikedPosts() and !in_array('special:disliked', array_map('strtolower', $tokens)))
			$this->processComplexToken('special', 'disliked', true);

		if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden') or !in_array('special:hidden', array_map('strtolower', $tokens)))
			$this->processComplexToken('special', 'hidden', true);

		if (count($tokens) > $config->browsing->maxSearchTokens)
			throw new SimpleException('Too many search tokens (maximum: ' . $config->browsing->maxSearchTokens . ')');
	}

	protected function processTeardown()
	{
		foreach ($this->tags as $item)
		{
			list ($tagName, $neg) = $item;
			$tag = TagModel::findByName($tagName);
			$innerStmt = new SqlSelectStatement();
			$innerStmt->setTable('post_tag');
			$innerStmt->setCriterion((new SqlConjunctionFunctor)
				->add(new SqlEqualsFunctor('post_tag.post_id', 'post.id'))
				->add(new SqlEqualsFunctor('post_tag.tag_id', new SqlBinding($tag->id))));
			$operator = new SqlExistsFunctor($innerStmt);
			if ($neg)
				$operator = new SqlNegationFunctor($operator);
			$this->statement->getCriterion()->add($operator);
		}

		$this->statement->addOrderBy('post.id',
			empty($this->statement->getOrderBy())
				? SqlSelectStatement::ORDER_DESC
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
			return SqlInFunctor::fromArray('post.id', SqlBinding::fromArray($ids));
		}

		elseif (in_array($key, ['fav', 'favs']))
		{
			$user = UserModel::findByNameOrEmail($value);
			$innerStmt = (new SqlSelectStatement)
				->setTable('favoritee')
				->setCriterion((new SqlConjunctionFunctor)
					->add(new SqlEqualsFunctor('favoritee.post_id', 'post.id'))
					->add(new SqlEqualsFunctor('favoritee.user_id', new SqlBinding($user->id))));
			return new SqlExistsFunctor($innerStmt);
		}

		elseif (in_array($key, ['comment', 'commenter']))
		{
			$user = UserModel::findByNameOrEmail($value);
			$innerStmt = (new SqlSelectStatement)
				->setTable('comment')
				->setCriterion((new SqlConjunctionFunctor)
					->add(new SqlEqualsFunctor('comment.post_id', 'post.id'))
					->add(new SqlEqualsFunctor('comment.commenter_id', new SqlBinding($user->id))));
			return new SqlExistsFunctor($innerStmt);
		}

		elseif (in_array($key, ['submit', 'upload', 'uploader', 'uploaded']))
		{
			$user = UserModel::findByNameOrEmail($value);
			return new SqlEqualsFunctor('uploader_id', new SqlBinding($user->id));
		}

		elseif (in_array($key, ['idmin', 'id_min']))
			return new SqlEqualsOrGreaterFunctor('post.id', new SqlBinding(intval($value)));

		elseif (in_array($key, ['idmax', 'id_max']))
			return new SqlEqualsOrLesserFunctor('post.id', new SqlBinding(intval($value)));

		elseif (in_array($key, ['scoremin', 'score_min']))
			return new SqlEqualsOrGreaterFunctor('score', new SqlBinding(intval($value)));

		elseif (in_array($key, ['scoremax', 'score_max']))
			return new SqlEqualsOrLesserFunctor('score', new SqlBinding(intval($value)));

		elseif (in_array($key, ['tagmin', 'tag_min']))
			return new SqlEqualsOrGreaterFunctor('tag_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['tagmax', 'tag_max']))
			return new SqlEqualsOrLesserFunctor('tag_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['favmin', 'fav_min']))
			return new SqlEqualsOrGreaterFunctor('fav_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['favmax', 'fav_max']))
			return new SqlEqualsOrLesserFunctor('fav_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['commentmin', 'comment_min']))
			return new SqlEqualsOrGreaterFunctor('comment_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['commentmax', 'comment_max']))
			return new SqlEqualsOrLesserFunctor('comment_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['date']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return (new SqlConjunctionFunctor)
				->add(new SqlEqualsOrLesserFunctor('upload_date', new SqlBinding($dateMax)))
				->add(new SqlEqualsOrGreaterFunctor('upload_date', new SqlBinding($dateMin)));
		}

		elseif (in_array($key, ['datemin', 'date_min']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return new SqlEqualsOrGreaterFunctor('upload_date', new SqlBinding($dateMin));
		}

		elseif (in_array($key, ['datemax', 'date_max']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return new SqlEqualsOrLesserFunctor('upload_date', new SqlBinding($dateMax));
		}

		elseif ($key == 'special')
		{
			$context = \Chibi\Registry::getContext();
			$value = strtolower($value);
			if (in_array($value, ['liked', 'likes']))
			{
				if (!$this->statement->isTableJoined('post_score'))
				{
					$this->statement->addLeftOuterJoin('post_score', (new SqlConjunctionFunctor)
						->add(new SqlEqualsFunctor('post_score.post_id', 'post.id'))
						->add(new SqlEqualsFunctor('post_score.user_id', new SqlBinding($context->user->id))));
				}
				return new SqlEqualsFunctor(new SqlIfNullFunctor('post_score.score', '0'), '1');
			}

			elseif (in_array($value, ['disliked', 'dislikes']))
			{
				if (!$this->statement->isTableJoined('post_score'))
				{
					$this->statement->addLeftOuterJoin('post_score', (new SqlConjunctionFunctor)
						->add(new SqlEqualsFunctor('post_score.post_id', 'post.id'))
						->add(new SqlEqualsFunctor('post_score.user_id', new SqlBinding($context->user->id))));
				}
				return new SqlEqualsFunctor(new SqlIfNullFunctor('post_score.score', '0'), '-1');
			}

			elseif ($value == 'hidden')
				return new SqlStringExpression('hidden');

			else
				throw new SimpleException('Invalid special token: ' . $value);
		}

		elseif ($key == 'type')
		{
			$value = strtolower($value);
			if ($value == 'swf')
				$type = PostType::Flash;
			elseif ($value == 'img')
				$type = PostType::Image;
			elseif ($value == 'yt' or $value == 'youtube')
				$type = PostType::Youtube;
			else
				throw new SimpleException('Invalid post type: ' . $value);

			return new SqlEqualsFunctor('type', new SqlBinding($type));
		}

		return null;
	}

	protected function processComplexToken($key, $value, $neg)
	{
		$criterion = $this->prepareCriterionForComplexToken($key, $value);
		if (!$criterion)
			return false;

		if ($neg)
			$criterion = new SqlNegationFunctor($criterion);

		$this->statement->getCriterion()->add($criterion);
		return true;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		$randomReset = true;

		if (in_array($orderByString, ['id']))
			$orderColumn = 'post.id';

		elseif (in_array($orderByString, ['date']))
			$orderColumn = 'upload_date';

		elseif (in_array($orderByString, ['comment', 'comments', 'commentcount', 'comment_count']))
			$orderColumn = 'comment_count';

		elseif (in_array($orderByString, ['fav', 'favs', 'favcount', 'fav_count']))
			$orderColumn = 'fav_count';

		elseif (in_array($orderByString, ['score']))
			$orderColumn = 'score';

		elseif (in_array($orderByString, ['tag', 'tags', 'tagcount', 'tag_count']))
			$orderColumn = 'tag_count';

		elseif (in_array($orderByString, ['commentdate', 'comment_date']))
			$orderColumn = 'comment_date';

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
			$orderColumn = new SqlSubstrFunctor(
				new SqlMultiplicationFunctor('post.id', $seed),
				new SqlAdditionFunctor(new SqlLengthFunctor('post.id'), '2'));
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
		$yearMin = $yearMax = intval($year);
		$monthMin = $monthMax = intval($month);
		$monthMin = $monthMin ?: 1;
		$monthMax = $monthMax ?: 12;
		$dayMin = $dayMax = intval($day);
		$dayMin = $dayMin ?: 1;
		$dayMax = $dayMax ?: intval(date('t', mktime(0, 0, 0, $monthMax, 1, $year)));
		$timeMin = mktime(0, 0, 0, $monthMin, $dayMin, $yearMin);
		$timeMax = mktime(0, 0, -1, $monthMax, $dayMax+1, $yearMax);
		return [$timeMin, $timeMax];
	}
}
