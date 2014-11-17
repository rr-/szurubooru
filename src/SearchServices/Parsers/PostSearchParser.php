<?php
namespace Szurubooru\SearchServices\Parsers;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\NotSupportedException;
use Szurubooru\SearchServices\Filters\IFilter;
use Szurubooru\SearchServices\Filters\PostFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\SearchServices\Requirements\RequirementCompositeValue;
use Szurubooru\SearchServices\Tokens\NamedSearchToken;
use Szurubooru\SearchServices\Tokens\SearchToken;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PrivilegeService;

class PostSearchParser extends AbstractSearchParser
{
	private $authService;
	private $privilegeService;

	public function __construct(
		AuthService $authService,
		PrivilegeService $privilegeService)
	{
		$this->authService = $authService;
		$this->privilegeService = $privilegeService;
	}

	protected function createFilter()
	{
		return new PostFilter;
	}

	protected function decorateFilterFromToken(IFilter $filter, SearchToken $token)
	{
		$requirement = new Requirement();
		$requirement->setType(PostFilter::REQUIREMENT_TAG);
		$requirement->setValue($this->createRequirementValue($token->getValue()));
		$requirement->setNegated($token->isNegated());
		$filter->addRequirement($requirement);
	}

	protected function decorateFilterFromNamedToken(IFilter $filter, NamedSearchToken $token)
	{
		$tokenKey = $token->getKey();
		$tokenValue = $token->getValue();

		$countAliases = ['tag_count' => 'tag', 'fav_count' => 'fav', 'score' => 'score'];
		foreach ($countAliases as $realKey => $baseAlias)
		{
			if ($this->matches($tokenKey, [$baseAlias . '_min', $baseAlias . '_max']))
			{
				$token = new NamedSearchToken();
				$token->setKey($realKey);
				$token->setValue(strpos($tokenKey, 'min') !== false ? $tokenValue . '..' : '..' . $tokenValue);
				return $this->decorateFilterFromNamedToken($filter, $token);
			}
		}

		$map =
		[
			[['id'], [$this, 'addIdRequirement']],
			[['hash', 'name'], [$this, 'addHashRequirement']],
			[['date', 'time'], [$this, 'addDateRequirement']],
			[['tag_count'], [$this, 'addTagCountRequirement']],
			[['fav_count'], [$this, 'addFavCountRequirement']],
			[['comment_count'], [$this, 'addCommentCountRequirement']],
			[['score'], [$this, 'addScoreRequirement']],
			[['uploader', 'submit', 'up'], [$this, 'addUploaderRequirement']],
			[['safety', 'rating'], [$this, 'addSafetyRequirement']],
			[['fav'], [$this, 'addFavRequirement']],
			[['type'], [$this, 'addTypeRequirement']],
			[['comment'], [$this, 'addCommentRequirement']],
		];

		foreach ($map as $item)
		{
			list ($aliases, $callback) = $item;
			if ($this->matches($tokenKey, $aliases))
			{
				return $callback($filter, $token);
			}
		}

		if ($this->matches($tokenKey, ['special']))
		{
			if ($this->matches($tokenValue, ['liked']))
			{
				$this->privilegeService->assertLoggedIn();
				return $this->addUserScoreRequirement(
					$filter,
					$this->authService->getLoggedInUser()->getName(),
					1,
					$token->isNegated());
			}

			if ($this->matches($tokenValue, ['disliked']))
			{
				$this->privilegeService->assertLoggedIn();
				return $this->addUserScoreRequirement(
					$filter,
					$this->authService->getLoggedInUser()->getName(),
					-1,
					$token->isNegated());
			}

			if ($this->matches($tokenValue, ['fav']))
			{
				$this->privilegeService->assertLoggedIn();
				$token = new NamedSearchToken();
				$token->setKey('fav');
				$token->setValue($this->authService->getLoggedInUser()->getName());
				return $this->decorateFilterFromNamedToken($filter, $token);
			}
		}

		throw new NotSupportedException();
	}

	protected function getOrderColumn($tokenText)
	{
		if ($this->matches($tokenText, ['random']))
			return PostFilter::ORDER_RANDOM;

		if ($this->matches($tokenText, ['id']))
			return PostFilter::ORDER_ID;

		if ($this->matches($tokenText, ['time', 'date']))
			return PostFilter::ORDER_LAST_EDIT_TIME;

		if ($this->matches($tokenText, ['score']))
			return PostFilter::ORDER_SCORE;

		if ($this->matches($tokenText, ['file_size']))
			return PostFilter::ORDER_FILE_SIZE;

		if ($this->matches($tokenText, ['tag_count', 'tags', 'tag']))
			return PostFilter::ORDER_TAG_COUNT;

		if ($this->matches($tokenText, ['fav_count', 'fags', 'fav']))
			return PostFilter::ORDER_FAV_COUNT;

		if ($this->matches($tokenText, ['comment_count', 'comments', 'comment']))
			return PostFilter::ORDER_COMMENT_COUNT;

		if ($this->matches($tokenText, ['fav_time', 'fav_date']))
			return PostFilter::ORDER_LAST_FAV_TIME;

		if ($this->matches($tokenText, ['comment_time', 'comment_date']))
			return PostFilter::ORDER_LAST_COMMENT_TIME;

		if ($this->matches($tokenText, ['feature_time', 'feature_date', 'featured', 'feature']))
			return PostFilter::ORDER_LAST_FEATURE_TIME;

		throw new NotSupportedException();
	}

	private function addIdRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_ID,
			self::ALLOW_COMPOSITE | self::ALLOW_RANGES);
	}

	private function addHashRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_HASH,
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
			PostFilter::REQUIREMENT_DATE,
			self::ALLOW_RANGES);
	}

	private function addTagCountRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_TAG_COUNT,
			self::ALLOW_COMPOSITE | self::ALLOW_RANGES);
	}

	private function addFavCountRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_FAV_COUNT,
			self::ALLOW_COMPOSITE | self::ALLOW_RANGES);
	}

	private function addCommentCountRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_COMMENT_COUNT,
			self::ALLOW_COMPOSITE | self::ALLOW_RANGES);
	}

	private function addScoreRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_SCORE,
			self::ALLOW_COMPOSITE | self::ALLOW_RANGES);
	}

	private function addUploaderRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_UPLOADER,
			self::ALLOW_COMPOSITE);
	}

	private function addSafetyRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_SAFETY,
			self::ALLOW_COMPOSITE,
			function ($value)
			{
				return EnumHelper::postSafetyFromString($value);
			});
	}

	private function addFavRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_FAVORITE,
			self::ALLOW_COMPOSITE);
	}

	private function addTypeRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_TYPE,
			self::ALLOW_COMPOSITE,
			function ($value)
			{
				return EnumHelper::postTypeFromString($value);
			});
	}

	private function addCommentRequirement($filter, $token)
	{
		$this->addRequirementFromToken(
			$filter,
			$token,
			PostFilter::REQUIREMENT_COMMENT,
			self::ALLOW_COMPOSITE);
	}

	private function addUserScoreRequirement($filter, $userName, $score, $isNegated)
	{
		$tokenValue = new RequirementCompositeValue();
		$tokenValue->setValues([$userName, $score]);
		$requirement = new Requirement();
		$requirement->setType(PostFilter::REQUIREMENT_USER_SCORE);
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
