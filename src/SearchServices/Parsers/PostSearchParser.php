<?php
namespace Szurubooru\SearchServices\Parsers;

class PostSearchParser extends AbstractSearchParser
{
	private $authService;

	public function __construct(\Szurubooru\Services\AuthService $authService)
	{
		$this->authService = $authService;
	}

	protected function createFilter()
	{
		return new \Szurubooru\SearchServices\Filters\PostFilter;
	}

	protected function decorateFilterFromToken($filter, $token)
	{
		$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
		$requirement->setType(\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_TAG);
		$requirement->setValue($this->createRequirementValue($token->getValue()));
		$requirement->setNegated($token->isNegated());
		$filter->addRequirement($requirement);
	}

	protected function decorateFilterFromNamedToken($filter, $token)
	{
		if ($token->getKey() === 'id')
			$this->addIdRequirement($filter, $token);

		elseif ($token->getKey() === 'hash')
			$this->addHashRequirement($filter, $token);

		elseif ($token->getKey() === 'date')
			$this->addDateRequirement($filter, $token);

		elseif ($token->getKey() === 'tag_count')
			$this->addTagCountRequirement($filter, $token);

		elseif ($token->getKey() === 'fav_count')
			$this->addFavCountRequirement($filter, $token);

		elseif ($token->getKey() === 'score')
			$this->addScoreRequirement($filter, $token);

		elseif ($token->getKey() === 'uploader')
			$this->addUploaderRequirement($filter, $token);

		elseif ($token->getKey() === 'safety')
			$this->addSafetyRequirement($filter, $token);

		elseif ($token->getKey() === 'fav')
			$this->addFavRequirement($filter, $token);

		elseif ($token->getKey() === 'type')
			$this->addTypeRequirement($filter, $token);

		elseif ($token->getKey() === 'comment')
			$this->addCommentRequirement($filter, $token);

		elseif ($token->getKey() === 'special' and $token->getValue() === 'liked' and $this->authService->isLoggedIn())
			$this->addUserScoreRequirement($filter, $this->authService->getLoggedInUser()->getName(), 1, $token->isNegated());

		elseif ($token->getKey() === 'special' and $token->getValue() === 'disliked' and $this->authService->isLoggedIn())
			$this->addUserScoreRequirement($filter, $this->authService->getLoggedInUser()->getName(), -1, $token->isNegated());

		elseif ($token->getKey() === 'special' and $token->getValue() === 'fav' and $this->authService->isLoggedIn())
		{
			$token = new \Szurubooru\SearchServices\Tokens\NamedSearchToken();
			$token->setKey('fav');
			$token->setValue($this->authService->getLoggedInUser()->getName());
			$this->decorateFilterFromNamedToken($filter, $token);
		}

		else
			throw new \BadMethodCallException('Not supported');
	}

	protected function getOrderColumn($token)
	{
		if ($token === 'id')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_ID;

		elseif ($token === 'fav_time')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_FAV_TIME;

		elseif ($token === 'fav_count')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_FAV_COUNT;

		elseif ($token === 'tag_count')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_TAG_COUNT;

		elseif ($token === 'time')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_LAST_EDIT_TIME;

		elseif ($token === 'score')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_SCORE;

		elseif ($token === 'file_size')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_FILE_SIZE;

		elseif ($token === 'random')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_RANDOM;

		elseif ($token === 'feature_time')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_LAST_FEATURE_TIME;

		elseif ($token === 'comment_time')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_LAST_COMMENT_TIME;

		elseif ($token === 'fav_time')
			return \Szurubooru\SearchServices\Filters\PostFilter::ORDER_LAST_FAV_TIME;

		throw new \BadMethodCallException('Not supported');
	}

	private function addIdRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_ID,
			self::ALLOW_COMPOSITE | self::ALLOW_RANGES);
	}

	private function addHashRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_HASH,
			self::ALLOW_COMPOSITE);
	}

	private function addDateRequirement($filter, $token)
	{
		if (substr_count($token->getValue(), '..') === 1)
		{
			list ($dateMin, $dateMax) = explode('..', $token->getValue());
			$timeMin = $this->dateToTime($dateMin)[0];
			$timeMax = $this->dateToTime($dateMax)[1];
		}
		else
		{
			$date = $token->getValue();
			list ($timeMin, $timeMax) = $this->dateToTime($date);
		}

		$finalString = '';
		if ($timeMin)
			$finalString .= date('c', $timeMin);
		$finalString .= '..';
		if ($timeMax)
			$finalString .= date('c', $timeMax);

		$token->setValue($finalString);
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_DATE,
			self::ALLOW_RANGES);
	}

	private function addTagCountRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_TAG_COUNT,
			self::ALLOW_COMPOSITE | self::ALLOW_RANGES);
	}

	private function addFavCountRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_FAV_COUNT,
			self::ALLOW_COMPOSITE | self::ALLOW_RANGES);
	}

	private function addScoreRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_SCORE,
			self::ALLOW_COMPOSITE | self::ALLOW_RANGES);
	}

	private function addUploaderRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_UPLOADER,
			self::ALLOW_COMPOSITE);
	}

	private function addSafetyRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_SAFETY,
			self::ALLOW_COMPOSITE,
			function ($value)
			{
				return \Szurubooru\Helpers\EnumHelper::postSafetyFromString($value);
			});
	}

	private function addFavRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_FAVORITE,
			self::ALLOW_COMPOSITE);
	}

	private function addTypeRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_TYPE,
			self::ALLOW_COMPOSITE,
			function ($value)
			{
				return \Szurubooru\Helpers\EnumHelper::postTypeFromSTring($value);
			});
	}

	private function addCommentRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_COMMENT,
			self::ALLOW_COMPOSITE);
	}

	private function addUserScoreRequirement($filter, $userName, $score, $isNegated)
	{
		$tokenValue = new \Szurubooru\SearchServices\Requirements\RequirementCompositeValue();
		$tokenValue->setValues([$userName, $score]);
		$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
		$requirement->setType(\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_USER_SCORE);
		$requirement->setValue($tokenValue);
		$requirement->setNegated($isNegated);
		$filter->addRequirement($requirement);
	}

	private function dateToTime($value)
	{
		$value = strtolower(trim($value));
		if (!$value)
		{
			return null;
		}
		elseif ($value === 'today')
		{
			$timeMin = mktime(0, 0, 0);
			$timeMax = mktime(24, 0, -1);
		}
		elseif ($value === 'yesterday')
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
			throw new \Exception('Invalid date format: ' . $value);

		return [$timeMin, $timeMax];
	}
}
