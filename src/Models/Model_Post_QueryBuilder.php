<?php
class Model_Post_QueryBuilder implements AbstractQueryBuilder
{
	private static $enableTokenLimit = true;

	public static function enableTokenLimit($enable)
	{
		self::$enableTokenLimit = $enable;
	}

	protected static function filterUserSafety($dbQuery)
	{
		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$dbQuery->addSql('safety')->in('(' . R::genSlots($allowedSafety) . ')');
		foreach ($allowedSafety as $s)
			$dbQuery->put($s);
	}

	protected static function filterUserHidden($dbQuery)
	{
		if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden'))
			$dbQuery->not()->addSql('hidden');
		else
			$dbQuery->addSql('1');
	}

	protected static function filterChain($dbQuery)
	{
		if (isset($dbQuery->__chained))
			$dbQuery->and();
		else
			$dbQuery->where();
		$dbQuery->__chained = true;
	}

	protected static function filterNegate($dbQuery)
	{
		$dbQuery->not();
	}

	protected static function filterTag($dbQuery, $val)
	{
		$dbQuery
			->exists()
			->open()
			->select('1')
			->from('post_tag')
			->innerJoin('tag')
			->on('post_tag.tag_id = tag.id')
			->where('post_id = post.id')
			->and('LOWER(tag.name) = LOWER(?)')->put($val)
			->close();
	}

	protected static function filterTokenId($context, $dbQuery, $val)
	{
		$ids = preg_split('/[;,]/', $val);
		$ids = array_map('intval', $ids);
		$dbQuery->addSql('id')->in('(' . R::genSlots($ids) . ')');
		foreach ($ids as $id)
			$dbQuery->put($id);
	}

	protected static function filterTokenIdMin($context, $dbQuery, $val)
	{
		$dbQuery->addSql('id >= ?')->put(intval($val));
	}

	protected static function filterTokenIdMax($context, $dbQuery, $val)
	{
		$dbQuery->addSql('id <= ?')->put(intval($val));
	}

	protected static function filterTokenScoreMin($context, $dbQuery, $val)
	{
		$dbQuery->addSql('score >= ?')->put(intval($val));
	}

	protected static function filterTokenScoreMax($context, $dbQuery, $val)
	{
		$dbQuery->addSql('score <= ?')->put(intval($val));
	}

	protected static function filterTokenTagMin($context, $dbQuery, $val)
	{
		$dbQuery->addSql('tag_count >= ?')->put(intval($val));
	}

	protected static function filterTokenTagMax($context, $dbQuery, $val)
	{
		$dbQuery->addSql('tag_count <= ?')->put(intval($val));
	}

	protected static function filterTokenFavMin($context, $dbQuery, $val)
	{
		$dbQuery->addSql('fav_count >= ?')->put(intval($val));
	}

	protected static function filterTokenFavMax($context, $dbQuery, $val)
	{
		$dbQuery->addSql('fav_count <= ?')->put(intval($val));
	}

	protected static function filterTokenCommentMin($context, $dbQuery, $val)
	{
		$dbQuery->addSql('comment_count >= ?')->put(intval($val));
	}

	protected static function filterTokenCommentMax($context, $dbQuery, $val)
	{
		$dbQuery->addSql('comment_count <= ?')->put(intval($val));
	}

	protected static function filterTokenSpecial($context, $dbQuery, $val)
	{
		$context = \Chibi\Registry::getContext();

		switch (strtolower($val))
		{
			case 'liked':
			case 'likes':
				$dbQuery
					->exists()
					->open()
					->select('1')
					->from('postscore')
					->where('post_id = post.id')
					->and('score > 0')
					->and('user_id = ?')->put($context->user->id)
					->close();
				break;

			case 'disliked':
			case 'dislikes':
				$dbQuery
					->exists()
					->open()
					->select('1')
					->from('postscore')
					->where('post_id = post.id')
					->and('score < 0')
					->and('user_id = ?')->put($context->user->id)
					->close();
				break;

			default:
				throw new SimpleException('Unknown special "' . $val . '"');
		}
	}

	protected static function filterTokenType($context, $dbQuery, $val)
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
		$dbQuery->addSql('type = ?')->put($type);
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

	protected static function filterTokenDate($context, $dbQuery, $val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		$dbQuery
			->addSql('upload_date >= ?')->and('upload_date <= ?')
			->put($timeMin)
			->put($timeMax);
	}

	protected static function filterTokenDateMin($context, $dbQuery, $val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		$dbQuery->addSql('upload_date >= ?')->put($timeMin);
	}

	protected static function filterTokenDateMax($context, $dbQuery, $val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		$dbQuery->addSql('upload_date <= ?')->put($timeMax);
	}

	protected static function filterTokenFav($context, $dbQuery, $val)
	{
		$dbQuery
			->exists()
			->open()
			->select('1')
			->from('favoritee')
			->innerJoin('user')
			->on('favoritee.user_id = user.id')
			->where('post_id = post.id')
			->and('LOWER(user.name) = LOWER(?)')->put($val)
			->close();
	}

	protected static function filterTokenFavs($context, $dbQuery, $val)
	{
		return self::filterTokenFav($context, $dbQuery, $val);
	}

	protected static function filterTokenComment($context, $dbQuery, $val)
	{
		$dbQuery
			->exists()
			->open()
			->select('1')
			->from('comment')
			->innerJoin('user')
			->on('commenter_id = user.id')
			->where('post_id = post.id')
			->and('LOWER(user.name) = LOWER(?)')->put($val)
			->close();
	}

	protected static function filterTokenCommenter($context, $dbQuery, $val)
	{
		return self::filterTokenComment($context, $dbQuery, $val);
	}

	protected static function filterTokenSubmit($context, $dbQuery, $val)
	{
		$dbQuery
			->addSql('uploader_id = ')
			->open()
			->select('user.id')
			->from('user')
			->where('LOWER(name) = LOWER(?)')->put($val)
			->close();
	}

	protected static function filterTokenUploader($context, $dbQuery, $val)
	{
		return self::filterTokenSubmit($context, $dbQuery, $val);
	}

	protected static function filterTokenUpload($context, $dbQuery, $val)
	{
		return self::filterTokenSubmit($context, $dbQuery, $val);
	}

	protected static function filterTokenUploaded($context, $dbQuery, $val)
	{
		return self::filterTokenSubmit($context, $dbQuery, $val);
	}

	protected static function filterTokenPrev($context, $dbQuery, $val)
	{
		self::__filterTokenPrevNext($context, $dbQuery, $val);
	}

	protected static function filterTokenNext($context, $dbQuery, $val)
	{
		$context->orderDir *= -1;
		self::__filterTokenPrevNext($context, $dbQuery, $val);
	}

	protected static function __filterTokenPrevNext($context, $dbQuery, $val)
	{
		$op1 = $context->orderDir == 1 ? '<' : '>';
		$op2 = $context->orderDir != 1 ? '<' : '>';
		$dbQuery
			->open()
				->open()
					->addSql($context->orderColumn . ' ' . $op1 . ' ')
					->open()
						->select($context->orderColumn)
						->from('post p2')
						->where('p2.id = ?')->put(intval($val))
					->close()
					->and('id != ?')->put($val)
				->close()
				->or()
				->open()
					->addSql($context->orderColumn . ' = ')
					->open()
						->select($context->orderColumn)
						->from('post p2')
						->where('p2.id = ?')->put(intval($val))
					->close()
					->and('id ' . $op1 . ' ?')->put(intval($val))
				->close()
			->close();
	}


	protected static function parseOrderToken($context, $val)
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

		$context->orderColumn = $orderColumn;
		$context->orderDir = $orderDir;
	}



	protected static function iterateTokens($tokens, $callback)
	{
		$unparsedTokens = [];

		foreach ($tokens as $token)
		{
			if ($token{0} == '-')
			{
				$token = substr($token, 1);
				$neg = true;
			}
			else
			{
				$neg = false;
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
				$unparsedTokens []= $token;
		}
		return $unparsedTokens;
	}

	public static function build($dbQuery, $query)
	{
		$config = \Chibi\Registry::getConfig();

		$dbQuery->from('post');

		self::filterChain($dbQuery);
		self::filterUserSafety($dbQuery);
		self::filterChain($dbQuery);
		self::filterUserHidden($dbQuery);

		/* query tokens */
		$tokens = array_filter(array_unique(explode(' ', $query)), function($x) { return $x != ''; });
		if (self::$enableTokenLimit and count($tokens) > $config->browsing->maxSearchTokens)
			throw new SimpleException('Too many search tokens (maximum: ' . $config->browsing->maxSearchTokens . ')');

		$context = new StdClass;
		$context->orderColumn = 'id';
		$context->orderDir = 1;

		$tokens = self::iterateTokens($tokens, function($neg, $key, $val) use ($context, $dbQuery, &$orderToken)
		{
			if ($key != 'order')
				return false;

			if ($neg)
				$orderToken = '-' . $val;
			else
				$orderToken = $val;
			self::parseOrderToken($context, $orderToken);

			return true;
		});


		$tokens = self::iterateTokens($tokens, function($neg, $key, $val) use ($context, $dbQuery)
		{
			if ($key !== null)
				return false;

			self::filterChain($dbQuery);
			if ($neg)
				self::filterNegate($dbQuery);
			self::filterTag($dbQuery, $val);
			return true;
		});

		$tokens = self::iterateTokens($tokens, function($neg, $key, $val) use ($context, $dbQuery)
		{
			$methodName = 'filterToken' . TextHelper::kebabCaseToCamelCase($key);
			if (!method_exists(__CLASS__, $methodName))
				return false;

			self::filterChain($dbQuery);
			if ($neg)
				self::filterNegate($dbQuery);
			self::$methodName($context, $dbQuery, $val);
			return true;
		});

		if (!empty($tokens))
			throw new SimpleException('Unknown search token "' . array_shift($tokens) . '"');

		$dbQuery->orderBy($context->orderColumn);
		if ($context->orderDir == 1)
			$dbQuery->desc();
		else
			$dbQuery->asc();

		$dbQuery->addSql(', id ');
		if ($context->orderDir == 1)
			$dbQuery->desc();
		else
			$dbQuery->asc();
	}
}
