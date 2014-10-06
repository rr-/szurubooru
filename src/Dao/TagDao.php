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

	protected function afterBatchSave(array $entities)
	{
		if (count($entities) > 0)
			$this->exportJson();
	}
}
