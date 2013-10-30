<?php
class Model_Post_QueryBuilder implements AbstractQueryBuilder
{
	protected static function attachTableCount($dbQuery, $tableName, $shortName)
	{
		$dbQuery
			->addSql(', ')
			->open()
			->select('COUNT(1)')
			->from($tableName)
			->where($tableName . '.post_id = post.id')
			->close()
			->as($shortName . '_count');
	}

	protected static function attachCommentCount($dbQuery)
	{
		self::attachTableCount($dbQuery, 'comment', 'comment');
	}

	protected static function attachFavCount($dbQuery)
	{
		self::attachTableCount($dbQuery, 'favoritee', 'fav');
	}

	protected static function attachTagCount($dbQuery)
	{
		self::attachTableCount($dbQuery, 'post_tag', 'tag');
	}

	protected static function filterUserSafety($dbQuery)
	{
		$context = \Chibi\Registry::getContext();
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

	protected static function filterTokenId($dbQuery, $val)
	{
		$ids = preg_split('/[;,]/', $val);
		$ids = array_map('intval', $ids);
		$dbQuery->addSql('id')->in('(' . R::genSlots($ids) . ')');
		foreach ($ids as $id)
			$dbQuery->put($id);
	}

	protected static function filterTokenIdMin($dbQuery, $val)
	{
		$dbQuery->addSql('id >= ?')->put(intval($val));
	}

	protected static function filterTokenIdMax($dbQuery, $val)
	{
		$dbQuery->addSql('id <= ?')->put(intval($val));
	}

	protected static function filterTokenTagMin($dbQuery, $val)
	{
		$dbQuery->addSql('tag_count >= ?')->put(intval($val));
	}

	protected static function filterTokenTagMax($dbQuery, $val)
	{
		$dbQuery->addSql('tag_count <= ?')->put(intval($val));
	}

	protected static function filterTokenFavMin($dbQuery, $val)
	{
		$dbQuery->addSql('fav_count >= ?')->put(intval($val));
	}

	protected static function filterTokenFavMax($dbQuery, $val)
	{
		$dbQuery->addSql('fav_count <= ?')->put(intval($val));
	}

	protected static function filterTokenCommentMin($dbQuery, $val)
	{
		$dbQuery->addSql('comment_count >= ?')->put(intval($val));
	}

	protected static function filterTokenCommentMax($dbQuery, $val)
	{
		$dbQuery->addSql('comment_count <= ?')->put(intval($val));
	}

	protected static function filterTokenType($dbQuery, $val)
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

	protected static function filterTokenDate($dbQuery, $val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		$dbQuery
			->addSql('upload_date >= ?')->and('upload_date <= ?')
			->put($timeMin)
			->put($timeMax);
	}

	protected static function filterTokenDateMin($dbQuery, $val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		$dbQuery->addSql('upload_date >= ?')->put($timeMin);
	}

	protected static function filterTokenDateMax($dbQuery, $val)
	{
		list ($timeMin, $timeMax) = self::__filterTokenDateParser($val);
		$dbQuery->addSql('upload_date <= ?')->put($timeMax);
	}

	protected static function filterTokenFav($dbQuery, $val)
	{
		$dbQuery
			->exists()
			->open()
			->select('1')
			->from('favoritee')
			->innerJoin('user')
			->on('favoritee.user_id = user.id')
			->where('post_id = post.id')
			->and('user.name = ?')->put($val)
			->close();
	}

	protected static function filterTokenFavs($dbQuery, $val)
	{
		return self::filterTokenFav($dbQuery, $val);
	}

	protected static function filterTokenFavitee($dbQuery, $val)
	{
		return self::filterTokenFav($dbQuery, $val);
	}

	protected static function filterTokenFaviter($dbQuery, $val)
	{
		return self::filterTokenFav($dbQuery, $val);
	}

	protected static function filterTokenSubmit($dbQuery, $val)
	{
		$dbQuery
			->addSql('uploader_id = ')
			->open()
			->select('user.id')
			->from('user')
			->where('name = ?')->put($val)
			->close();
	}

	protected static function filterTokenUploader($dbQuery, $val)
	{
		return self::filterTokenSubmit($dbQuery, $val);
	}

	protected static function filterTokenUpload($dbQuery, $val)
	{
		return self::filterTokenSubmit($dbQuery, $val);
	}

	protected static function filterTokenUploaded($dbQuery, $val)
	{
		return self::filterTokenSubmit($dbQuery, $val);
	}



	protected static function order($dbQuery, $val)
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
				$orderColumn = 'post.id';
				break;
			case 'date':
				$orderColumn = 'post.upload_date';
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

		if ($randomReset)
			unset($_SESSION['browsing-seed']);

		$dbQuery->orderBy($orderColumn);
		if ($orderDir == 1)
			$dbQuery->desc();
		else
			$dbQuery->asc();
	}



	public static function build($dbQuery, $query)
	{
		$config = \Chibi\Registry::getConfig();
		$context = \Chibi\Registry::getContext();

		self::attachCommentCount($dbQuery);
		self::attachFavCount($dbQuery);
		self::attachTagCount($dbQuery);

		$dbQuery->from('post');

		self::filterChain($dbQuery);
		self::filterUserSafety($dbQuery);
		self::filterChain($dbQuery);
		self::filterUserHidden($dbQuery);

		/* query tokens */
		$tokens = array_filter(array_unique(explode(' ', $query)), function($x) { return $x != ''; });
		if (count($tokens) > $config->browsing->maxSearchTokens)
			throw new SimpleException('Too many search tokens (maximum: ' . $config->browsing->maxSearchTokens . ')');

		$orderToken = 'id';
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
				self::filterChain($dbQuery);
				if ($neg)
					self::filterNegate($dbQuery);
				self::filterTag($dbQuery, $token);
				continue;
			}

			$key = substr($token, 0, $pos);
			$val = substr($token, $pos + 1);

			$methodName = 'filterToken' . TextHelper::kebabCaseToCamelCase($key);
			if (method_exists(__CLASS__, $methodName))
			{
				self::filterChain($dbQuery);
				if ($neg)
					self::filterNegate($dbQuery);
				self::$methodName($dbQuery, $val);
			}

			elseif ($key == 'order')
			{
				if ($neg)
					$orderToken = $val;
				else
					$orderToken = '-' . $val;
			}

			else
			{
				throw new SimpleException('Unknown key "' . $key . '"');
			}
		}

		self::order($dbQuery, $orderToken);
	}
}
