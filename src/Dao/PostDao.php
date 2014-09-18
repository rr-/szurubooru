<?php
namespace Szurubooru\Dao;

class PostDao extends AbstractDao implements ICrudDao
{
	public function __construct(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		parent::__construct(
			$databaseConnection,
			'posts',
			new \Szurubooru\Dao\EntityConverters\PostEntityConverter());
	}

	public function findByName($name)
	{
		return $this->findOneBy('name', $name);
	}

	public function findByContentChecksum($checksum)
	{
		return $this->findOneBy('contentChecksum', $checksum);
	}

	protected function afterLoad(\Szurubooru\Entities\Entity $entity)
	{
		$entity->setLazyLoader('tags', function(\Szurubooru\Entities\Post $post)
			{
				return $this->getTags($post);
			});
	}

	protected function afterSave(\Szurubooru\Entities\Entity $entity)
	{
		$this->syncTags($entity->getId(), $entity->getTags());
	}

	private function getTags(\Szurubooru\Entities\Post $post)
	{
		$postId = $post->getId();
		$result = [];
		$query = $this->fpdo->from('postTags')->where('postId', $postId)->select('tagName');
		foreach ($query as $arrayEntity)
			$result[] = $arrayEntity['tagName'];
		return $result;
	}

	private function syncTags($postId, array $tags)
	{
		$existingTags = array_map(
			function($arrayEntity)
			{
				return $arrayEntity['tagName'];
			},
			iterator_to_array($this->fpdo->from('postTags')->where('postId', $postId)));
		$tagRelationsToInsert = array_diff($tags, $existingTags);
		$tagRelationsToDelete = array_diff($existingTags, $tags);
		$this->createMissingTags($tags);
		foreach ($tagRelationsToInsert as $tag)
		{
			$this->fpdo->insertInto('postTags')->values(['postId' => $postId, 'tagName' => $tag])->execute();
		}
		foreach ($tagRelationsToDelete as $tag)
		{
			$this->fpdo->deleteFrom('postTags')->where('postId', $postId)->and('tagName', $tag)->execute();
		}
	}

	private function createMissingTags(array $tags)
	{
		if (empty($tags))
			return;

		$tagsNotToCreate = array_map(
			function($arrayEntity)
			{
				return $arrayEntity['name'];
			},
			iterator_to_array($this->fpdo->from('tags')->where('name', $tags)));

		$tagsToCreate = array_diff($tags, $tagsNotToCreate);

		foreach ($tagsToCreate as $tag)
		{
			$this->fpdo->insertInto('tags')->values(['name' => $tag])->execute();
		}
	}
}
