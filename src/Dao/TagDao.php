<?php
namespace Szurubooru\Dao;

class TagDao extends AbstractDao implements ICrudDao
{
	public function __construct(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		parent::__construct(
			$databaseConnection,
			'tags',
			new \Szurubooru\Dao\EntityConverters\TagEntityConverter());
	}

	public function findByNames($tagNames)
	{
		return $this->findBy('name', $tagNames);
	}

	public function findByPostId($postId)
	{
		$query = $this->fpdo->from('postTags')->where('postId', $postId);
		$tagIds = array_map(function($arrayEntity)
			{
				return $arrayEntity['tagId'];
			},
			iterator_to_array($query));
		return $this->findByIds($tagIds);
	}

	public function createMissingTags(array $tagNames)
	{
		$tagNames = array_filter(array_unique($tagNames));
		if (empty($tagNames))
			return;

		$tagNamesNotToCreate = array_map(
			function ($tag)
			{
				return $tag->getName();
			},
			$this->findByNames($tagNames));

		$tagNamesToCreate = array_udiff($tagNames, $tagNamesNotToCreate, 'strcasecmp');

		foreach ($tagNamesToCreate as $tagName)
		{
			$tag = new \Szurubooru\Entities\Tag;
			$tag->setName($tagName);
			$this->save($tag);
		}
	}
}
