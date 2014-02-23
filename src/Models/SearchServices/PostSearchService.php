<?php
class PostSearchService extends AbstractSearchService
{
	private static $enableTokenLimit = true;

	public static function getPostIdsAround($searchQuery, $postId)
	{
		return Database::transaction(function() use ($searchQuery, $postId)
		{
			$stmt = new SqlRawStatement('CREATE TEMPORARY TABLE IF NOT EXISTS post_search(id INTEGER PRIMARY KEY, post_id INTEGER)');
			Database::exec($stmt);

			$stmt = new SqlDeleteStatement();
			$stmt->setTable('post_search');
			Database::exec($stmt);

			$innerStmt = new SqlSelectStatement($searchQuery);
			$innerStmt->setColumn('id');
			self::decorate($innerStmt, $searchQuery);
			$stmt = new SqlInsertStatement();
			$stmt->setTable('post_search');
			$stmt->setSource(['post_id'], $innerStmt);
			Database::exec($stmt);

			$stmt = new SqlSelectStatement();
			$stmt->setTable('post_search');
			$stmt->setColumn('id');
			$stmt->setCriterion(new SqlEqualsOperator('post_id', new SqlBinding($postId)));
			$rowId = Database::fetchOne($stmt)['id'];

			//it's possible that given post won't show in search results:
			//it can be hidden, it can have prohibited safety etc.
			if (!$rowId)
				return [null, null];

			$rowId = intval($rowId);
			$stmt->setColumn('post_id');

			$stmt->setCriterion(new SqlEqualsOperator('id', new SqlBinding($rowId - 1)));
			$nextPostId = Database::fetchOne($stmt)['post_id'];

			$stmt->setCriterion(new SqlEqualsOperator('id', new SqlBinding($rowId + 1)));
			$prevPostId = Database::fetchOne($stmt)['post_id'];

			return [$prevPostId, $nextPostId];
		});
	}

	public static function enableTokenLimit($enable)
	{
		self::$enableTokenLimit = $enable;
	}

	protected static function decorateNegation(SqlExpression $criterion, $negative)
	{
		return !$negative
			? $criterion
			: new SqlNegationOperator($criterion);
	}

	protected static function filterUserSafety(SqlSelectStatement $stmt)
	{
		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$stmt->getCriterion()->add(SqlInOperator::fromArray('safety', SqlBinding::fromArray($allowedSafety)));
	}

	protected static function filterTag(SqlSelectStatement $stmt, $val, $neg)
	{
		$tag = TagModel::findByName($val);
		$innerStmt = new SqlSelectStatement();
		$innerStmt->setTable('post_tag');
		$innerStmt->setCriterion((new SqlConjunction)
			->add(new SqlEqualsOperator('post_id', 'post.id'))
			->add(new SqlEqualsOperator('post_tag.tag_id', new SqlBinding($tag->id))));
		$stmt->getCriterion()->add(self::decorateNegation(new SqlExistsOperator($innerStmt), $neg));
	}

	protected static function filterTokenId($val)
	{
		$ids = preg_split('/[;,]/', $val);
		$ids = array_map('intval', $ids);
		return SqlInOperator::fromArray('id', $ids);
	}

	protected static function filterTokenIdMin($val)
	{
		return new SqlEqualsOrGreaterOperator('id', new SqlBinding(intval($val)));
	}

	protected static function filterTokenIdMax($val)
	{
		return new SqlEqualsOrLesserOperator('id', new SqlBinding(intval($val)));
	}

	protected static function filterTokenScoreMin($val)
	{
		return new SqlEqualsOrGreaterOperator('score', new SqlBinding(intval($val)));
	}

	protected static function filterTokenScoreMax($val)
	{
		return new SqlEqualsOrLesserOperator('score', new SqlBinding(intval($val)));
	}

	protected static function filterTokenTagMin($val)
	{
		return new SqlEqualsOrGreaterOperator('tag_count', new SqlBinding(intval($val)));
	}

	protected static function filterTokenTagMax($val)
	{
		return new SqlEqualsOrLesserOperator('tag_count', new SqlBinding(intval($val)));
	}

	protected static function filterTokenFavMin($val)
	{
		return new SqlEqualsOrGreaterOperator('fav_count', new SqlBinding(intval($val)));
	}

	protected static function filterTokenFavMax($val)
	{
		return new SqlEqualsOrLesserOperator('fav_count', new SqlBinding(intval($val)));
	}

	protected static function filterTokenCommentMin($val)
	{
		return new SqlEqualsOrGreaterOperator('comment_count', new SqlBinding(intval($val)));
	}

	protected static function filterTokenCommentMax($val)
	{
		return new SqlEqualsOrLesserOperator('comment_count', new SqlBinding(intval($val)));
	}

	protected static function filterTokenSpecial($val)
	{
		$context = \Chibi\Registry::getContext();

		switch ($val)
		{
			case 'liked':
			case 'likes':
				$innerStmt = new SqlSelectStatement();
				$innerStmt->setTable('post_score');
				$innerStmt->setCriterion((new SqlConjunction)
					->add(new SqlGreaterOperator('score', '0'))
					->add(new SqlEqualsOperator('post_id', 'post.id'))
					->add(new SqlEqualsOperator('user_id', new SqlBinding($context->user->id))));
				return new SqlExistsOperator($innerStmt);

			case 'disliked':
			case 'dislikes':
				$innerStmt = new SqlSelectStatement();
				$innerStmt->setTable('post_score');
				$innerStmt->setCriterion((new SqlConjunction)
					->add(new SqlLesserOperator('score', '0'))
					->add(new SqlEqualsOperator('post_id', 'post.id'))
					->add(new SqlEqualsOperator('user_id', new SqlBinding($context->user->id))));
				return new SqlExistsOperator($innerStmt);

			case 'hidden':
				return new SqlStringExpression('hidden');

			default:
				throw new SimpleException('Unknown special "' . $val . '"');
		}
	}

	protected static function filterTokenType($val)
	{
		switch ($val)
		{
			case 'swf':
				$type = PostType::Flash;
				break;
			case 'img':
				$type = PostType::Image;
				break;
			case 'yt':
			case 'youtube':
				$type = PostType::Youtube;
				break;
			default:
				throw new SimpleException('Unknown type "' . $val . '"');
		}
		return new SqlEqualsOperator('type', new SqlBinding($type));
	}

	protected static function __filterTokenDateParser($val)
	{
		list ($year, $month, $day) = explode('-', $val . '-0-0');
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

	protected static function filterTokenDate($val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		return (new SqlConjunction)
			->add(new SqlEqualsOrGreaterOperator('upload_date', new SqlBinding($timeMin)))
			->add(new SqlEqualsOrLesserOperator('upload_date', new SqlBinding($timeMax)));
	}

	protected static function filterTokenDateMin($val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		return new SqlEqualsOrGreaterOperator('upload_date', new SqlBinding($timeMin));
	}

	protected static function filterTokenDateMax($val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		return new SqlEqualsOrLesserOperator('upload_date', new SqlBinding($timeMax));
	}

	protected static function filterTokenFav($val)
	{
		$user = UserModel::findByNameOrEmail($val);
		$innerStmt = (new SqlSelectStatement)
			->setTable('favoritee')
			->setCriterion((new SqlConjunction)
				->add(new SqlEqualsOperator('post_id', 'post.id'))
				->add(new SqlEqualsOperator('favoritee.user_id', new SqlBinding($user->id))));
		return new SqlExistsOperator($innerStmt);
	}

	protected static function filterTokenFavs($val)
	{
		return self::filterTokenFav($val);
	}

	protected static function filterTokenComment($val)
	{
		$user = UserModel::findByNameOrEmail($val);
		$innerStmt = (new SqlSelectStatement)
			->setTable('comment')
			->setCriterion((new SqlConjunction)
				->add(new SqlEqualsOperator('post_id', 'post.id'))
				->add(new SqlEqualsOperator('commenter_id', new SqlBinding($user->id))));
		return new SqlExistsOperator($innerStmt);
	}

	protected static function filterTokenCommenter($val)
	{
		return self::filterTokenComment($searchContext, $stmt, $val);
	}

	protected static function filterTokenSubmit($val)
	{
		$user = UserModel::findByNameOrEmail($val);
		return new SqlEqualsOperator('uploader_id', new SqlBinding($user->id));
	}

	protected static function filterTokenUploader($val)
	{
		return self::filterTokenSubmit($val);
	}

	protected static function filterTokenUpload($val)
	{
		return self::filterTokenSubmit($val);
	}

	protected static function filterTokenUploaded($val)
	{
		return self::filterTokenSubmit($val);
	}



	protected static function changeOrder($stmt, $val, $neg = true)
	{
		$randomReset = true;

		$orderDir = SqlSelectStatement::ORDER_DESC;
		if (substr($val, -4) == 'desc')
		{
			$orderDir = SqlSelectStatement::ORDER_DESC;
			$val = rtrim(substr($val, 0, -4), ',');
		}
		elseif (substr($val, -3) == 'asc')
		{
			$orderDir = SqlSelectStatement::ORDER_ASC;
			$val = rtrim(substr($val, 0, -3), ',');
		}
		if ($neg)
		{
			$orderDir = $orderDir == SqlSelectStatement::ORDER_DESC
				? SqlSelectStatement::ORDER_ASC
				: SqlSelectStatement::ORDER_DESC;
		}

		switch ($val)
		{
			case 'id':
				$orderColumn = 'id';
				break;
			case 'date':
				$orderColumn = 'upload_date';
				break;
			case 'comment':
			case 'comments':
			case 'commentcount':
			case 'comment_count':
				$orderColumn = 'comment_count';
				break;
			case 'fav':
			case 'favs':
			case 'favcount':
			case 'fav_count':
				$orderColumn = 'fav_count';
				break;
			case 'score':
				$orderColumn = 'score';
				break;
			case 'tag':
			case 'tags':
			case 'tagcount':
			case 'tag_count':
				$orderColumn = 'tag_count';
				break;
			case 'random':
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
				break;
			default:
				throw new SimpleException('Unknown key "' . $val . '"');
		}

		if ($randomReset and isset($_SESSION['browsing-seed']))
			unset($_SESSION['browsing-seed']);

		$stmt->setOrderBy($orderColumn, $orderDir);
	}



	public static function decorate(SqlSelectStatement $stmt, $searchQuery)
	{
		$config = \Chibi\Registry::getConfig();

		$stmt->setTable('post');
		$stmt->setCriterion(new SqlConjunction());

		self::filterUserSafety($stmt);

		/* query tokens */
		$tokens = array_filter(array_unique(preg_split('/\s+/', strtolower($searchQuery))));
		if (self::$enableTokenLimit and count($tokens) > $config->browsing->maxSearchTokens)
			throw new SimpleException('Too many search tokens (maximum: ' . $config->browsing->maxSearchTokens . ')');

		if (\Chibi\Registry::getContext()->user->hasEnabledHidingDislikedPosts() and !in_array('special:disliked', $tokens))
			$tokens []= '-special:disliked';
		if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden') or !in_array('special:hidden', $tokens))
			$tokens []= '-special:hidden';

		$searchContext = new StdClass;
		$searchContext->orderColumn = 'id';
		$searchContext->orderDir = 1;

		foreach ($tokens as $token)
		{
			$neg = false;
			if ($token{0} == '-')
			{
				$neg = true;
				$token = substr($token, 1);
			}

			if (strpos($token, ':') !== false)
			{
				list ($key, $val) = explode(':', $token);
				$key = strtolower($key);
				if ($key == 'order')
				{
					self::changeOrder($stmt, $val, $neg);
				}
				else
				{
					$methodName = 'filterToken' . TextHelper::kebabCaseToCamelCase($key);
					if (!method_exists(__CLASS__, $methodName))
						throw new SimpleException('Unknown search token "' . $key . '"');

					$criterion = self::$methodName($val);
					$criterion = self::decorateNegation($criterion, $neg);
					$stmt->getCriterion()->add($criterion);
				}
			}
			else
			{
				self::filterTag($stmt, $token, $neg);
			}
		}

		$stmt->addOrderBy('id',
			empty($stmt->getOrderBy())
				? SqlSelectStatement::ORDER_DESC
				: $stmt->getOrderBy()[0][1]);
	}
}
