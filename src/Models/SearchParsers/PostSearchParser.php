<?php
class PostSearchParser extends AbstractSearchParser
{
	private $tags;

	protected function processSetup(&$tokens)
	{
		$config = \Chibi\Registry::getConfig();

		$this->tags = [];
		$this->statement->setCriterion(new SqlConjunction());

		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$this->statement->getCriterion()->add(SqlInOperator::fromArray('safety', SqlBinding::fromArray($allowedSafety)));

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
			$innerStmt->setCriterion((new SqlConjunction)
				->add(new SqlEqualsOperator('post_id', 'post.id'))
				->add(new SqlEqualsOperator('post_tag.tag_id', new SqlBinding($tag->id))));
			$operator = new SqlExistsOperator($innerStmt);
			if ($neg)
				$operator = new SqlNegationOperator($operator);
			$this->statement->getCriterion()->add($operator);
		}

		$this->statement->addOrderBy('id',
			empty($this->statement->getOrderBy())
				? SqlSelectStatement::ORDER_DESC
				: $this->statement->getOrderBy()[0][1]);
	}

	protected function processSimpleToken($value, $neg)
	{
		$this->tags []= [$value, $neg];
		return true;
	}

	protected static function getCriterionForComplexToken($key, $value)
	{
		if (in_array($key, ['id', 'ids']))
		{
			$ids = preg_split('/[;,]/', $value);
			$ids = array_map('intval', $ids);
			return SqlInOperator::fromArray('id', SqlBinding::fromArray($ids));
		}

		elseif (in_array($key, ['fav', 'favs']))
		{
			$user = UserModel::findByNameOrEmail($value);
			$innerStmt = (new SqlSelectStatement)
				->setTable('favoritee')
				->setCriterion((new SqlConjunction)
					->add(new SqlEqualsOperator('post_id', 'post.id'))
					->add(new SqlEqualsOperator('favoritee.user_id', new SqlBinding($user->id))));
			return new SqlExistsOperator($innerStmt);
		}

		elseif (in_array($key, ['comment', 'commenter']))
		{
			$user = UserModel::findByNameOrEmail($value);
			$innerStmt = (new SqlSelectStatement)
				->setTable('comment')
				->setCriterion((new SqlConjunction)
					->add(new SqlEqualsOperator('post_id', 'post.id'))
					->add(new SqlEqualsOperator('commenter_id', new SqlBinding($user->id))));
			return new SqlExistsOperator($innerStmt);
		}

		elseif (in_array($key, ['submit', 'upload', 'uploader', 'uploaded']))
		{
			$user = UserModel::findByNameOrEmail($value);
			return new SqlEqualsOperator('uploader_id', new SqlBinding($user->id));
		}

		elseif (in_array($key, ['idmin', 'id_min']))
			return new SqlEqualsOrGreaterOperator('id', new SqlBinding(intval($value)));

		elseif (in_array($key, ['idmax', 'id_max']))
			return new SqlEqualsOrLesserOperator('id', new SqlBinding(intval($value)));

		elseif (in_array($key, ['scoremin', 'score_min']))
			return new SqlEqualsOrGreaterOperator('score', new SqlBinding(intval($value)));

		elseif (in_array($key, ['scoremax', 'score_max']))
			return new SqlEqualsOrLesserOperator('score', new SqlBinding(intval($value)));

		elseif (in_array($key, ['tagmin', 'tag_min']))
			return new SqlEqualsOrGreaterOperator('tag_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['tagmax', 'tag_max']))
			return new SqlEqualsOrLesserOperator('tag_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['favmin', 'fav_min']))
			return new SqlEqualsOrGreaterOperator('fav_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['favmax', 'fav_max']))
			return new SqlEqualsOrLesserOperator('fav_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['commentmin', 'comment_min']))
			return new SqlEqualsOrGreaterOperator('comment_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['commentmax', 'comment_max']))
			return new SqlEqualsOrLesserOperator('comment_count', new SqlBinding(intval($value)));

		elseif (in_array($key, ['datemin', 'date_min', 'date']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return new SqlEqualsOrGreaterOperator('upload_date', new SqlBinding($dateMin));
		}

		elseif (in_array($key, ['datemax', 'date_max', 'date']))
		{
			list ($dateMin, $dateMax) = self::parseDate($value);
			return new SqlEqualsOrLesserOperator('upload_date', new SqlBinding($dateMax));
		}

		elseif ($key == 'special')
		{
			$context = \Chibi\Registry::getContext();
			$value = strtolower($value);
			if (in_array($value, ['liked', 'likes']))
			{
				$innerStmt = new SqlSelectStatement();
				$innerStmt->setTable('post_score');
				$innerStmt->setCriterion((new SqlConjunction)
					->add(new SqlGreaterOperator('score', '0'))
					->add(new SqlEqualsOperator('post_id', 'post.id'))
					->add(new SqlEqualsOperator('user_id', new SqlBinding($context->user->id))));
				return new SqlExistsOperator($innerStmt);
			}

			elseif (in_array($value, ['disliked', 'dislikes']))
			{
				$innerStmt = new SqlSelectStatement();
				$innerStmt->setTable('post_score');
				$innerStmt->setCriterion((new SqlConjunction)
					->add(new SqlLesserOperator('score', '0'))
					->add(new SqlEqualsOperator('post_id', 'post.id'))
					->add(new SqlEqualsOperator('user_id', new SqlBinding($context->user->id))));
				return new SqlExistsOperator($innerStmt);
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

			return new SqlEqualsOperator('type', new SqlBinding($type));
		}

		return null;
	}

	protected function processComplexToken($key, $value, $neg)
	{
		$criterion = self::getCriterionForComplexToken($key, $value);
		if (!$criterion)
			return false;

		if ($neg)
			$criterion = new SqlNegationOperator($criterion);

		$this->statement->getCriterion()->add($criterion);
		return true;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		$randomReset = true;

		if (in_array($orderByString, ['id']))
			$orderColumn = 'id';

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
			$orderColumn = 'SUBSTR(id * ' . $seed .', LENGTH(id) + 2)';
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
