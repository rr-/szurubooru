<?php
namespace Szurubooru\Search\Parsers;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\NotSupportedException;
use Szurubooru\Search\Filters\IFilter;
use Szurubooru\Search\Filters\PostFilter;
use Szurubooru\Search\Requirements\Requirement;
use Szurubooru\Search\Requirements\RequirementCompositeValue;
use Szurubooru\Search\Tokens\NamedSearchToken;
use Szurubooru\Search\Tokens\SearchToken;
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

        $countAliases =
        [
            'tag_count' => 'tags',
            'fav_count' => 'favs',
            'score' => 'score',
            'comment_count' => 'comments',
            'note_count' => 'notes',
        ];
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
            [['tag_count', 'tags'], [$this, 'addTagCountRequirement']],
            [['fav_count', 'favs'], [$this, 'addFavCountRequirement']],
            [['comment_count', 'comments'], [$this, 'addCommentCountRequirement']],
            [['note_count', 'notes'], [$this, 'addNoteCountRequirement']],
            [['score'], [$this, 'addScoreRequirement']],
            [['uploader', 'uploader', 'uploaded', 'submit', 'submitter', 'submitted'], [$this, 'addUploaderRequirement']],
            [['safety', 'rating'], [$this, 'addSafetyRequirement']],
            [['fav'], [$this, 'addFavRequirement']],
            [['type'], [$this, 'addTypeRequirement']],
            [['comment', 'comment_author', 'commented'], [$this, 'addCommentAuthorRequirement']],
        ];

        foreach ($map as $item)
        {
            list ($aliases, $callback) = $item;
            if ($this->matches($tokenKey, $aliases))
                return $callback($filter, $token);
        }

        if ($this->matches($tokenKey, ['special']))
        {
            $specialMap =
            [
                [['liked'], [$this, 'addOwnLikedRequirement']],
                [['disliked'], [$this, 'addOwnDislikedRequirement']],
                [['fav'], [$this, 'addOwnFavRequirement']],
            ];

            foreach ($specialMap as $item)
            {
                list ($aliases, $callback) = $item;
                if ($this->matches($token->getValue(), $aliases))
                    return $callback($filter, $token);
            }

            throw new NotSupportedException(
                'Unknown value for special search term: ' . $token->getValue()
                . '. Possible search terms: '
                . join(', ', array_map(function($term) { return join('/', $term[0]); }, $specialMap)));
        }

        throw new NotSupportedException('Unknown search term: ' . $token->getKey()
            . '. Possible search terms: special, '
            . join(', ', array_map(function($term) { return join('/', $term[0]); }, $map)));
    }

    protected function getOrderColumnMap()
    {
        return
        [
            [['id'],                                    PostFilter::ORDER_ID],
            [['random'],                                PostFilter::ORDER_RANDOM],
            [['edit_time', 'edit_date'],                PostFilter::ORDER_LAST_EDIT_TIME],
            [['score'],                                 PostFilter::ORDER_SCORE],
            [['file_size'],                             PostFilter::ORDER_FILE_SIZE],
            [['tag_count', 'tags', 'tag'],              PostFilter::ORDER_TAG_COUNT],
            [['fav_count', 'fags', 'fav'],              PostFilter::ORDER_FAV_COUNT],
            [['comment_count', 'comments', 'comment'],  PostFilter::ORDER_COMMENT_COUNT],
            [['note_count', 'notes', 'note'],           PostFilter::ORDER_NOTE_COUNT],
            [['fav_time', 'fav_date'],                  PostFilter::ORDER_LAST_FAV_TIME],
            [['comment_time', 'comment_date'],          PostFilter::ORDER_LAST_COMMENT_TIME],
            [['feature_time', 'feature_date'],          PostFilter::ORDER_LAST_FEATURE_TIME],
            [['feature_count', 'features', 'featured'], PostFilter::ORDER_FEATURE_COUNT],
        ];
    }

    private function addOwnLikedRequirement($filter, $token)
    {
        $this->privilegeService->assertLoggedIn();
        $this->addUserScoreRequirement(
            $filter,
            $this->authService->getLoggedInUser()->getName(),
            1,
            $token->isNegated());
    }

    private function addOwnDislikedRequirement($filter, $token)
    {
        $this->privilegeService->assertLoggedIn();
        $this->addUserScoreRequirement(
            $filter,
            $this->authService->getLoggedInUser()->getName(),
            -1,
            $token->isNegated());
    }

    private function addOwnFavRequirement($filter, $token)
    {
        $this->privilegeService->assertLoggedIn();
        $token = new NamedSearchToken();
        $token->setKey('fav');
        $token->setValue($this->authService->getLoggedInUser()->getName());
        $this->decorateFilterFromNamedToken($filter, $token);
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
            PostFilter::REQUIREMENT_CREATION_TIME,
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

    private function addNoteCountRequirement($filter, $token)
    {
        $this->addRequirementFromToken(
            $filter,
            $token,
            PostFilter::REQUIREMENT_NOTE_COUNT,
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

    private function addCommentAuthorRequirement($filter, $token)
    {
        $this->addRequirementFromToken(
            $filter,
            $token,
            PostFilter::REQUIREMENT_COMMENT_AUTHOR,
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
