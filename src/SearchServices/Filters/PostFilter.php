<?php
namespace Szurubooru\SearchServices\Filters;

class PostFilter extends BasicFilter implements IFilter
{
	const ORDER_ID = 'id';
	const ORDER_FAV_TIME = 'lastFavTime';
	const ORDER_FAV_COUNT = 'favCount';
	const ORDER_TAG_COUNT = 'tagCount';
	const ORDER_SCORE = 'score';
	const ORDER_LAST_EDIT_TIME = 'lastEditTime';
	const ORDER_FILE_SIZE = 'originalFileSize';

	const REQUIREMENT_TAG = 'tag';
	const REQUIREMENT_ID = 'id';
	const REQUIREMENT_DATE = 'uploadTime';
	const REQUIREMENT_HASH = 'name';
	const REQUIREMENT_TAG_COUNT = 'tagCount';
	const REQUIREMENT_FAV_COUNT = 'favCount';
	const REQUIREMENT_SCORE = 'score';
	const REQUIREMENT_UPLOADER = 'uploader.name';
	const REQUIREMENT_SAFETY = 'safety';
	const REQUIREMENT_FAVORITE = 'favoritedBy.name';
	const REQUIREMENT_TYPE = 'contentType';
	const REQUIREMENT_USER_SCORE = 'userScore';

	public function __construct()
	{
		$this->setOrder([self::ORDER_ID => self::ORDER_DESC]);
	}
}
