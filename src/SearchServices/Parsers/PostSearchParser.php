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

		elseif ($token->getKey() === 'comment_count')
			$this->addCommentCountRequirement($filter, $token);

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

		elseif ($token->getKey() === 'special' && $token->getValue() === 'liked')
		{
			$this->privilegeService->assertLoggedIn();
			$this->addUserScoreRequirement($filter, $this->authService->getLoggedInUser()->getName(), 1, $token->isNegated());
		}

		elseif ($token->getKey() === 'special' && $token->getValue() === 'disliked')
		{
			$this->privilegeService->assertLoggedIn();
			$this->addUserScoreRequirement($filter, $this->authService->getLoggedInUser()->getName(), -1, $token->isNegated());
		}

		elseif ($token->getKey() === 'special' && $token->getValue() === 'fav')
		{
			$this->privilegeService->assertLoggedIn();
			$token = new NamedSearchToken();
			$token->setKey('fav');
			$token->setValue($this->authService->getLoggedInUser()->getName());
			$this->decorateFilterFromNamedToken($filter, $token);
		}

		else
			throw new NotSupportedException();
	}

	protected function getOrderColumn($tokenText)
	{
		if ($tokenText === 'random')
			return PostFilter::ORDER_RANDOM;

		elseif ($tokenText === 'id')
			return PostFilter::ORDER_ID;

		elseif ($tokenText === 'time' || $tokenText === 'date')
			return PostFilter::ORDER_LAST_EDIT_TIME;

		elseif ($tokenText === 'score')
			return PostFilter::ORDER_SCORE;

		elseif ($tokenText === 'file_size')
			return PostFilter::ORDER_FILE_SIZE;

		elseif ($tokenText === 'tag_count')
			return PostFilter::ORDER_TAG_COUNT;

		elseif ($tokenText === 'fav_count')
			return PostFilter::ORDER_FAV_COUNT;

		elseif ($tokenText === 'comment_count')
			return PostFilter::ORDER_COMMENT_COUNT;

		elseif ($tokenText === 'fav_time' || $tokenText === 'fav_date')
			return PostFilter::ORDER_LAST_FAV_TIME;

		elseif ($tokenText === 'comment_time' || $tokenText === 'comment_date')
			return PostFilter::ORDER_LAST_COMMENT_TIME;

		elseif ($tokenText === 'feature_time' || $tokenText === 'feature_date')
			return PostFilter::ORDER_LAST_FEATURE_TIME;

		else
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
