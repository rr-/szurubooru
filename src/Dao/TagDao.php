<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\PostEntityConverter;
use Szurubooru\Dao\EntityConverters\TagEntityConverter;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Tag;

class TagDao extends AbstractDao implements ICrudDao
{
	const TAG_RELATION_IMPLICATION = 1;
	const TAG_RELATION_SUGGESTION = 2;

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

	public function findSiblings($tagName)
	{
		$tag = $this->findByName($tagName);
		if (!$tag)
			return [];
		$tagId = $tag->getId();
		$query = $this->fpdo->from($this->tableName)
			->disableSmartJoin()
			->innerJoin('postTags pt1 ON pt1.tagId = tags.id')
			->innerJoin('postTags pt2 ON pt2.postId = pt1.postId')
			->where('pt2.tagId', $tagId)
			->groupBy('tags.id')
			->orderBy('tags.usages DESC, name ASC');
		$arrayEntities = iterator_to_array($query);
		return $this->arrayToEntities($arrayEntities);
	}

	public function deleteUnused()
	{
		$this->deleteBy('usages', 0);
	}

	protected function afterLoad(Entity $tag)
	{
		$tag->setLazyLoader(
			Tag::LAZY_LOADER_IMPLIED_TAGS,
			function (Tag $tag)
			{
				return $this->findImpliedTags($tag);
			});

		$tag->setLazyLoader(
			Tag::LAZY_LOADER_SUGGESTED_TAGS,
			function (Tag $tag)
			{
				return $this->findSuggested($tag);
			});
	}

	protected function afterSave(Entity $tag)
	{
		$this->syncImpliedTags($tag);
		$this->syncSuggestedTags($tag);
	}

	private function findImpliedTags(Tag $tag)
	{
		return $this->findRelatedTagsByType($tag, self::TAG_RELATION_IMPLICATION);
	}

	private function findSuggested(Tag $tag)
	{
		return $this->findRelatedTagsByType($tag, self::TAG_RELATION_SUGGESTION);
	}

	private function syncImpliedTags($tag)
	{
		$this->syncRelatedTagsByType($tag, $tag->getImpliedTags(), self::TAG_RELATION_IMPLICATION);
	}

	private function syncSuggestedTags($tag)
	{
		$this->syncRelatedTagsByType($tag, $tag->getSuggestedTags(), self::TAG_RELATION_SUGGESTION);
	}

	private function syncRelatedTagsByType(Tag $tag, array $relatedTags, $type)
	{
		$this->fpdo->deleteFrom('tagRelations')
			->where('tag1id', $tag->getId())
			->where('type', $type)
			->execute();

		$relatedTagIds = array_filter(array_unique(array_map(
			function ($tag)
			{
				if (!$tag->getId())
					throw new \RuntimeException('Unsaved entities found');
				return $tag->getId();
			},
			$relatedTags)));

		foreach ($relatedTagIds as $tagId)
		{
			$this->fpdo
				->insertInto('tagRelations')
				->values([
					'tag1id' => $tag->getId(),
					'tag2id' => $tagId,
					'type' => $type])
				->execute();
		}
	}

	public function export()
	{
		$exported = [];
		foreach ($this->fpdo->from('tags') as $arrayEntity)
		{
			$exported[$arrayEntity['id']] = [
				'name' => $arrayEntity['name'],
				'usages' => intval($arrayEntity['usages']),
				'banned' => boolval($arrayEntity['banned'])
			];
		}

		//upgrades on old databases
		try
		{
			$relations = iterator_to_array($this->fpdo->from('tagRelations'));
		}
		catch (\Exception $e)
		{
			$relations = [];
		}

		foreach ($relations as $arrayEntity)
		{
			$key1 = $arrayEntity['tag1id'];
			$key2 = $arrayEntity['tag2id'];
			$type = intval($arrayEntity['type']);
			if ($type === self::TAG_RELATION_IMPLICATION)
				$target = 'implications';
			elseif ($type === self::TAG_RELATION_SUGGESTION)
				$target = 'suggestions';
			else
				continue;

			if (!isset($exported[$key1]) or !isset($exported[$key2]))
				continue;

			if (!isset($exported[$key1][$target]))
				$exported[$key1][$target] = [];

			$exported[$key1][$target][] = $exported[$key2]['name'];
		}

		return array_values($exported);
	}

	private function findRelatedTagsByType(Tag $tag, $type)
	{
		$tagId = $tag->getId();
		$query = $this->fpdo->from($this->tableName)
			->disableSmartJoin()
			->innerJoin('tagRelations tr ON tags.id = tr.tag2id')
			->where('tr.type', $type)
			->where('tr.tag1id', $tagId);
		$arrayEntities = iterator_to_array($query);
		return $this->arrayToEntities($arrayEntities);
	}
}
