<?php
class PostSearchService extends AbstractSearchService
{
	private static $enableTokenLimit = true;

	public static function enableTokenLimit($enable)
	{
		self::$enableTokenLimit = $enable;
	}

	protected static function filterUserSafety(SqlQuery $sqlQuery)
	{
		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		if (empty($allowedSafety))
			$sqlQuery->raw('0');
		else
			$sqlQuery->raw('safety')->in()->genSlots($allowedSafety)->put($allowedSafety);
	}

	protected static function filterUserHidden(SqlQuery $sqlQuery)
	{
		if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden'))
			$sqlQuery->not('hidden');
		else
			$sqlQuery->raw('1');
	}

	protected static function filterChain(SqlQuery $sqlQuery)
	{
		if (isset($sqlQuery->__chained))
			$sqlQuery->and();
		else
			$sqlQuery->where();
		$sqlQuery->__chained = true;
	}

	protected static function filterNegate(SqlQuery $sqlQuery)
	{
		$sqlQuery->not();
	}

	protected static function filterTag($sqlQuery, $val)
	{
		$tag = TagModel::findByName($val);
		$sqlQuery
			->exists()
			->open()
			->select('1')
			->from('post_tag')
			->where('post_id = post.id')
			->and('post_tag.tag_id = ?')->put($tag->id)
			->close();
	}

	protected static function filterTokenId($searchContext, SqlQuery $sqlQuery, $val)
	{
		$ids = preg_split('/[;,]/', $val);
		$ids = array_map('intval', $ids);
		if (empty($ids))
			$sqlQuery->raw('0');
		else
			$sqlQuery->raw('id')->in()->genSlots($ids)->put($ids);
	}

	protected static function filterTokenIdMin($searchContext, SqlQuery $sqlQuery, $val)
	{
		$sqlQuery->raw('id >= ?')->put(intval($val));
	}

	protected static function filterTokenIdMax($searchContext, SqlQuery $sqlQuery, $val)
	{
		$sqlQuery->raw('id <= ?')->put(intval($val));
	}

	protected static function filterTokenScoreMin($searchContext, SqlQuery $sqlQuery, $val)
	{
		$sqlQuery->raw('score >= ?')->put(intval($val));
	}

	protected static function filterTokenScoreMax($searchContext, SqlQuery $sqlQuery, $val)
	{
		$sqlQuery->raw('score <= ?')->put(intval($val));
	}

	protected static function filterTokenTagMin($searchContext, SqlQuery $sqlQuery, $val)
	{
		$sqlQuery->raw('tag_count >= ?')->put(intval($val));
	}

	protected static function filterTokenTagMax($searchContext, SqlQuery $sqlQuery, $val)
	{
		$sqlQuery->raw('tag_count <= ?')->put(intval($val));
	}

	protected static function filterTokenFavMin($searchContext, SqlQuery $sqlQuery, $val)
	{
		$sqlQuery->raw('fav_count >= ?')->put(intval($val));
	}

	protected static function filterTokenFavMax($searchContext, SqlQuery $sqlQuery, $val)
	{
		$sqlQuery->raw('fav_count <= ?')->put(intval($val));
	}

	protected static function filterTokenCommentMin($searchContext, SqlQuery $sqlQuery, $val)
	{
		$sqlQuery->raw('comment_count >= ?')->put(intval($val));
	}

	protected static function filterTokenCommentMax($searchContext, SqlQuery $sqlQuery, $val)
	{
		$sqlQuery->raw('comment_count <= ?')->put(intval($val));
	}

	protected static function filterTokenSpecial($searchContext, SqlQuery $sqlQuery, $val)
	{
		$context = \Chibi\Registry::getContext();

		switch (strtolower($val))
		{
			case 'liked':
			case 'likes':
				$sqlQuery
					->exists()
					->open()
					->select('1')
					->from('post_score')
					->where('post_id = post.id')
					->and('score > 0')
					->and('user_id = ?')->put($context->user->id)
					->close();
				break;

			case 'disliked':
			case 'dislikes':
				$sqlQuery
					->exists()
					->open()
					->select('1')
					->from('post_score')
					->where('post_id = post.id')
					->and('score < 0')
					->and('user_id = ?')->put($context->user->id)
					->close();
				break;

			default:
				throw new SimpleException('Unknown special "' . $val . '"');
		}
	}

	protected static function filterTokenType($searchContext, SqlQuery $sqlQuery, $val)
	{
		switch (strtolower($val))
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
		$sqlQuery->raw('type = ?')->put($type);
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

	protected static function filterTokenDate($searchContext, SqlQuery $sqlQuery, $val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		$sqlQuery
			->raw('upload_date >= ?')->put($timeMin)
			->and('upload_date <= ?')->put($timeMax);
	}

	protected static function filterTokenDateMin($searchContext, SqlQuery $sqlQuery, $val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		$sqlQuery->raw('upload_date >= ?')->put($timeMin);
	}

	protected static function filterTokenDateMax($searchContext, SqlQuery $sqlQuery, $val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		$sqlQuery->raw('upload_date <= ?')->put($timeMax);
	}

	protected static function filterTokenFav($searchContext, SqlQuery $sqlQuery, $val)
	{
		$user = UserModel::findByNameOrEmail($val);
		$sqlQuery
			->exists()
			->open()
			->select('1')
			->from('favoritee')
			->where('post_id = post.id')
			->and('favoritee.user_id = ?')->put($user->id)
			->close();
	}

	protected static function filterTokenFavs($searchContext, SqlQuery $sqlQuery, $val)
	{
		return self::filterTokenFav($searchContext, $sqlQuery, $val);
	}

	protected static function filterTokenComment($searchContext, SqlQuery $sqlQuery, $val)
	{
		$user = UserModel::findByNameOrEmail($val);
		$sqlQuery
			->exists()
			->open()
			->select('1')
			->from('comment')
			->where('post_id = post.id')
			->and('commenter_id = ?')->put($user->id)
			->close();
	}

	protected static function filterTokenCommenter($searchContext, SqlQuery $sqlQuery, $val)
	{
		return self::filterTokenComment($searchContext, $sqlQuery, $val);
	}

	protected static function filterTokenSubmit($searchContext, SqlQuery $sqlQuery, $val)
	{
		$user = UserModel::findByNameOrEmail($val);
		$sqlQuery->raw('uploader_id = ?')->put($user->id);
	}

	protected static function filterTokenUploader($searchContext, SqlQuery $sqlQuery, $val)
	{
		return self::filterTokenSubmit($searchContext, $sqlQuery, $val);
	}

	protected static function filterTokenUpload($searchContext, SqlQuery $sqlQuery, $val)
	{
		return self::filterTokenSubmit($searchContext, $sqlQuery, $val);
	}

	protected static function filterTokenUploaded($searchContext, SqlQuery $sqlQuery, $val)
	{
		return self::filterTokenSubmit($searchContext, $sqlQuery, $val);
	}

	protected static function filterTokenPrev($searchContext, SqlQuery $sqlQuery, $val)
	{
		self::__filterTokenPrevNext($searchContext, $sqlQuery, $val);
	}

	protected static function filterTokenNext($searchContext, SqlQuery $sqlQuery, $val)
	{
		$searchContext->orderDir *= -1;
		self::__filterTokenPrevNext($searchContext, $sqlQuery, $val);
	}

	protected static function __filterTokenPrevNext($searchContext, SqlQuery $sqlQuery, $val)
	{
		$op1 = $searchContext->orderDir == 1 ? '<' : '>';
		$op2 = $searchContext->orderDir != 1 ? '<' : '>';
		$sqlQuery
			->open()
				->open()
					->raw($searchContext->orderColumn . ' ' . $op1 . ' ')
					->open()
						->select($searchContext->orderColumn)
						->from('post p2')
						->where('p2.id = ?')->put(intval($val))
					->close()
					->and('id != ?')->put($val)
				->close()
				->or()
				->open()
					->raw($searchContext->orderColumn . ' = ')
					->open()
						->select($searchContext->orderColumn)
						->from('post p2')
						->where('p2.id = ?')->put(intval($val))
					->close()
					->and('id ' . $op1 . ' ?')->put(intval($val))
				->close()
			->close();
	}


	protected static function parseOrderToken($searchContext, $val)
	{
		$randomReset = true;

		$orderDir = 1;
		if (substr($val, -4) == 'desc')
		{
			$orderDir = 1;
			$val = rtrim(substr($val, 0, -4), ',');
		}
		elseif (substr($val, -3) == 'asc')
		{
			$orderDir = -1;
			$val = rtrim(substr($val, 0, -3), ',');
		}
		if ($val{0} == '-')
		{
			$orderDir *= -1;
			$val = substr($val, 1);
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
				$orderColumn = 'comment_count';
				break;
			case 'fav':
			case 'favs':
			case 'favcount':
				$orderColumn = 'fav_count';
				break;
			case 'score':
				$orderColumn = 'score';
				break;
			case 'tag':
			case 'tags':
			case 'tagcount':
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

		$searchContext->orderColumn = $orderColumn;
		$searchContext->orderDir = $orderDir;
	}



	protected static function iterateTokens($tokens, $callback)
	{
		$unparsedTokens = [];

		foreach ($tokens as $origToken)
		{
			$token = $origToken;
			$neg = false;
			if ($token{0} == '-')
			{
				$token = substr($token, 1);
				$neg = true;
			}

			$pos = strpos($token, ':');
			if ($pos === false)
			{
				$key = null;
				$val = $token;
			}
			else
			{
				$key = strtolower(substr($token, 0, $pos));
				$val = substr($token, $pos + 1);
			}

			$parsed = $callback($neg, $key, $val);

			if (!$parsed)
				$unparsedTokens []= $origToken;
		}
		return $unparsedTokens;
	}

	public static function decorate(SqlQuery $sqlQuery, $searchQuery)
	{
		$config = \Chibi\Registry::getConfig();

		$sqlQuery->from('post');

		self::filterChain($sqlQuery);
		self::filterUserSafety($sqlQuery);
		self::filterChain($sqlQuery);
		self::filterUserHidden($sqlQuery);

		/* query tokens */
		$tokens = array_filter(array_unique(explode(' ', $searchQuery)), function($x) { return $x != ''; });
		if (self::$enableTokenLimit and count($tokens) > $config->browsing->maxSearchTokens)
			throw new SimpleException('Too many search tokens (maximum: ' . $config->browsing->maxSearchTokens . ')');

		if (\Chibi\Registry::getContext()->user->hasEnabledHidingDislikedPosts())
			$tokens []= '-special:disliked';

		$searchContext = new StdClass;
		$searchContext->orderColumn = 'id';
		$searchContext->orderDir = 1;

		$tokens = self::iterateTokens($tokens, function($neg, $key, $val) use ($searchContext, $sqlQuery, &$orderToken)
		{
			if ($key != 'order')
				return false;

			if ($neg)
				$orderToken = '-' . $val;
			else
				$orderToken = $val;
			self::parseOrderToken($searchContext, $orderToken);

			return true;
		});


		$tokens = self::iterateTokens($tokens, function($neg, $key, $val) use ($searchContext, $sqlQuery)
		{
			if ($key !== null)
				return false;

			self::filterChain($sqlQuery);
			if ($neg)
				self::filterNegate($sqlQuery);
			self::filterTag($sqlQuery, $val);
			return true;
		});

		$tokens = self::iterateTokens($tokens, function($neg, $key, $val) use ($searchContext, $sqlQuery)
		{
			$methodName = 'filterToken' . TextHelper::kebabCaseToCamelCase($key);
			if (!method_exists(__CLASS__, $methodName))
				return false;

			self::filterChain($sqlQuery);
			if ($neg)
				self::filterNegate($sqlQuery);
			self::$methodName($searchContext, $sqlQuery, $val);
			return true;
		});

		if (!empty($tokens))
			throw new SimpleException('Unknown search token "' . array_shift($tokens) . '"');

		$sqlQuery->orderBy($searchContext->orderColumn);
		if ($searchContext->orderDir == 1)
			$sqlQuery->desc();
		else
			$sqlQuery->asc();

		if ($searchContext->orderColumn != 'id')
		{
			$sqlQuery->raw(', id');
			if ($searchContext->orderDir == 1)
				$sqlQuery->desc();
			else
				$sqlQuery->asc();
		}
	}
}
