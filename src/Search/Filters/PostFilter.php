<?php
namespace Szurubooru\Search\Filters;

class PostFilter extends BasicFilter implements IFilter
{
    const ORDER_ID = 'posts.id';
    const ORDER_FAV_COUNT = 'posts.favCount';
    const ORDER_TAG_COUNT = 'posts.tagCount';
    const ORDER_COMMENT_COUNT = 'posts.commentCount';
    const ORDER_NOTE_COUNT = 'posts.noteCount';
    const ORDER_SCORE = 'posts.score';
    const ORDER_CREATION_TIME = 'posts.creationTime';
    const ORDER_LAST_EDIT_TIME = 'posts.lastEditTime';
    const ORDER_FILE_SIZE = 'posts.originalFileSize';
    const ORDER_LAST_COMMENT_TIME = 'posts.lastCommentCreationTime';
    const ORDER_LAST_FAV_TIME = 'posts.lastFavTime';
    const ORDER_LAST_FEATURE_TIME = 'posts.lastFeatureTime';
    const ORDER_FEATURE_COUNT = 'posts.featureCount';

    const REQUIREMENT_TAG = 'posts.tag';
    const REQUIREMENT_ID = 'posts.id';
    const REQUIREMENT_CREATION_TIME = 'posts.creationTime';
    const REQUIREMENT_LAST_EDIT_TIME = 'posts.lastEditTime';
    const REQUIREMENT_HASH = 'posts.name';
    const REQUIREMENT_TAG_COUNT = 'posts.tagCount';
    const REQUIREMENT_FAV_COUNT = 'posts.favCount';
    const REQUIREMENT_COMMENT_COUNT = 'posts.commentCount';
    const REQUIREMENT_NOTE_COUNT = 'posts.noteCount';
    const REQUIREMENT_FEATURE_COUNT = 'posts.featureCount';
    const REQUIREMENT_SCORE = 'posts.score';
    const REQUIREMENT_UPLOADER = 'uploader.name';
    const REQUIREMENT_SAFETY = 'posts.safety';
    const REQUIREMENT_FAVORITE = 'favoritedBy.name';
    const REQUIREMENT_COMMENT_AUTHOR = 'commentedBy.name';
    const REQUIREMENT_TYPE = 'posts.contentType';
    const REQUIREMENT_USER_SCORE = 'posts.userScore';
    const REQUIREMENT_TUMBLEWEED = 'tumbleweed';
    const REQUIREMENT_IMAGE_WIDTH = 'posts.imageWidth';
    const REQUIREMENT_IMAGE_HEIGHT = 'posts.imageHeight';
    const REQUIREMENT_IMAGE_AREA = 'posts.imageWidth*posts.imageHeight';

    public function __construct()
    {
        $this->setOrder([self::ORDER_ID => self::ORDER_DESC]);
    }
}
