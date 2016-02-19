<?php
namespace Szurubooru\Search\ParserConfigs;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\NotSupportedException;
use Szurubooru\Search\Filters\IFilter;
use Szurubooru\Search\Filters\PostFilter;
use Szurubooru\Search\ParserConfigs\AbstractSearchParserConfig;
use Szurubooru\Search\Requirements\Requirement;
use Szurubooru\Search\Requirements\RequirementSingleValue;
use Szurubooru\Search\Requirements\RequirementCompositeValue;
use Szurubooru\Search\Tokens\NamedSearchToken;
use Szurubooru\Search\Tokens\SearchToken;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PrivilegeService;

class PostSearchParserConfig extends AbstractSearchParserConfig
{
    private $authService;
    private $privilegeService;

    public function __construct(
        AuthService $authService,
        PrivilegeService $privilegeService)
    {
        $this->authService = $authService;
        $this->privilegeService = $privilegeService;

        $this->defineOrder(PostFilter::ORDER_ID, ['id']);
        $this->defineOrder(PostFilter::ORDER_RANDOM, ['random']);
        $this->defineOrder(PostFilter::ORDER_CREATION_TIME, ['creation_time', 'creation_date', 'date']);
        $this->defineOrder(PostFilter::ORDER_LAST_EDIT_TIME, ['edit_time', 'edit_date']);
        $this->defineOrder(PostFilter::ORDER_SCORE, ['score']);
        $this->defineOrder(PostFilter::ORDER_FILE_SIZE, ['file_size']);
        $this->defineOrder(PostFilter::ORDER_TAG_COUNT, ['tag_count', 'tags', 'tag']);
        $this->defineOrder(PostFilter::ORDER_FAV_COUNT, ['fav_count', 'fags', 'fav']);
        $this->defineOrder(PostFilter::ORDER_COMMENT_COUNT, ['comment_count', 'comments', 'comment']);
        $this->defineOrder(PostFilter::ORDER_NOTE_COUNT, ['note_count', 'notes', 'note']);
        $this->defineOrder(PostFilter::ORDER_LAST_FAV_TIME, ['fav_time', 'fav_date']);
        $this->defineOrder(PostFilter::ORDER_LAST_COMMENT_TIME, ['comment_time', 'comment_date']);
        $this->defineOrder(PostFilter::ORDER_LAST_FEATURE_TIME, ['feature_time', 'feature_date']);
        $this->defineOrder(PostFilter::ORDER_FEATURE_COUNT, ['feature_count', 'features', 'featured']);

        $this->defineBasicTokenParser(
            function(SearchToken $token)
            {
                $requirement = new Requirement();
                $requirement->setNegated($token->isNegated());
                $requirement->setType(PostFilter::REQUIREMENT_TAG);
                $requirement->setValue(new RequirementSingleValue($token->getValue()));
                return $requirement;
            });

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_ID,
            ['id'],
            self::ALLOW_COMPOSITE | self::ALLOW_RANGE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_HASH,
            ['hash', 'name'],
            self::ALLOW_COMPOSITE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_CREATION_TIME,
            ['creation_date', 'creation_time', 'date', 'time'],
            function ($value)
            {
                return self::createDateRequirementValue($value);
            });

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_LAST_EDIT_TIME,
            ['edit_date', 'edit_time'],
            function ($value)
            {
                return self::createDateRequirementValue($value);
            });

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_TAG_COUNT,
            ['tag_count', 'tags'],
            self::ALLOW_COMPOSITE | self::ALLOW_RANGE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_FAV_COUNT,
            ['fav_count', 'favs'],
            self::ALLOW_COMPOSITE | self::ALLOW_RANGE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_COMMENT_COUNT,
            ['comment_count', 'comments'],
            self::ALLOW_COMPOSITE | self::ALLOW_RANGE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_NOTE_COUNT,
            ['note_count', 'notes'],
            self::ALLOW_COMPOSITE | self::ALLOW_RANGE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_FEATURE_COUNT,
            ['feature_count', 'featured'],
            self::ALLOW_COMPOSITE | self::ALLOW_RANGE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_SCORE,
            ['score'],
            self::ALLOW_COMPOSITE | self::ALLOW_RANGE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_UPLOADER,
            ['upload', 'uploader', 'uploader', 'uploaded', 'submit', 'submitter', 'submitted'],
            self::ALLOW_COMPOSITE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_SAFETY,
            ['safety', 'rating'],
            function ($value)
            {
                return self::createRequirementValue(
                    EnumHelper::postSafetyFromString($value),
                    self::ALLOW_COMPOSITE);
            });

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_FAVORITE,
            ['fav'],
            self::ALLOW_COMPOSITE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_TYPE,
            ['type'],
            function ($value)
            {
                return new RequirementSingleValue(
                    EnumHelper::postTypeFromString($value),
                    self::ALLOW_COMPOSITE);
            });

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_COMMENT_AUTHOR,
            ['comment', 'comment_author', 'commented'],
            self::ALLOW_COMPOSITE);

        $this->defineSpecialTokenParser(
            ['liked'],
            function (SearchToken $token)
            {
                return $this->createOwnScoreRequirement(1, $token->isNegated());
            });

        $this->defineSpecialTokenParser(
            ['disliked'],
            function (SearchToken $token)
            {
                return $this->createOwnScoreRequirement(-1, $token->isNegated());
            });

        $this->defineSpecialTokenParser(
            ['fav'],
            function (SearchToken $token)
            {
                $this->privilegeService->assertLoggedIn();
                $token = new NamedSearchToken();
                $token->setKey('fav');
                $token->setValue($this->authService->getLoggedInUser()->getName());
                return $this->getRequirementForNamedToken($token);
            });

        $this->defineSpecialTokenParser(
            ['tumbleweed'],
            function (SearchToken $token)
            {
                $requirement = new Requirement();
                $requirement->setType(PostFilter::REQUIREMENT_TUMBLEWEED);
                $requirement->setNegated($token->isNegated());
                return $requirement;
            });

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_IMAGE_WIDTH,
            ['image_width', 'posts'],
            self::ALLOW_COMPOSITE | self::ALLOW_RANGE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_IMAGE_HEIGHT,
            ['image_height', 'posts'],
            self::ALLOW_COMPOSITE | self::ALLOW_RANGE);

        $this->defineNamedTokenParser(
            PostFilter::REQUIREMENT_IMAGE_AREA,
            ['image_area', 'posts'],
            self::ALLOW_COMPOSITE | self::ALLOW_RANGE);
    }

    public function createFilter()
    {
        return new PostFilter;
    }

    private function createOwnScoreRequirement($score, $isNegated)
    {
        $this->privilegeService->assertLoggedIn();
        $userName = $this->authService->getLoggedInUser()->getName();
        $tokenValue = new RequirementCompositeValue();
        $tokenValue->setValues([$userName, $score]);
        $requirement = new Requirement();
        $requirement->setType(PostFilter::REQUIREMENT_USER_SCORE);
        $requirement->setValue($tokenValue);
        $requirement->setNegated($isNegated);
        return $requirement;
    }
}
