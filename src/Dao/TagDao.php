<?php
namespace Szurubooru\Dao;

class TagDao extends AbstractDao implements ICrudDao
{
	private $fileService;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Services\FileService $fileService)
	{
		parent::__construct(
			$databaseConnection,
			'tags',
			new \Szurubooru\Dao\EntityConverters\TagEntityConverter());

		$this->fileService = $fileService;
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

	public function exportJson()
	{
		$tags = [];
		foreach ($this->findAll() as $tag)
		{
			$tags[$tag->getName()] = $tag->getUsages();
		}
		$json = json_encode($tags);
		$this->fileService->save('tags.json', $json);
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

		$tags = [];
		foreach ($tagNamesToCreate as $tagName)
		{
			$tag = new \Szurubooru\Entities\Tag;
			$tag->setName($tagName);
			$tags[] = $tag;
		}
		$this->batchSave($tags);
	}

	protected function afterBatchSave(array $entities)
	{
		if (count($entities) > 0)
			$this->exportJson();
	}
}
