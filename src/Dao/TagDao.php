<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\PostEntityConverter;
use Szurubooru\Dao\EntityConverters\TagEntityConverter;
use Szurubooru\DatabaseConnection;

class TagDao extends AbstractDao implements ICrudDao
{
	public function __construct(DatabaseConnection $databaseConnection)
	{
		parent::__construct(
			$databaseConnection,
			'tags',
			new TagEntityConverter());
	}

	public function findByName($tagName)
	{
		return $this->findOneBy('name', $tagName);
	}

	public function findByNames($tagNames)
	{
		return $this->findBy('name', $tagNames);
	}

	public function findByPostId($postId)
	{
		return $this->findByPostIds([$postId]);
	}

	public function findByPostIds($postIds)
	{
		$query = $this->fpdo->from($this->tableName)
			->disableSmartJoin()
			->innerJoin('postTags ON postTags.tagId = tags.id')
			->where('postTags.postId', $postIds);
		$arrayEntities = iterator_to_array($query);
		return $this->arrayToEntities($arrayEntities);
	}

	public function deleteUnused()
	{
		$this->deleteBy('usages', 0);
	}
}
