<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\PostEntityConverter;
use Szurubooru\Dao\EntityConverters\TagEntityConverter;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Tag;
use Szurubooru\Search\Filters\TagFilter;
use Szurubooru\Search\Requirements\Requirement;

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
		$query = $this->pdo->from($this->tableName)
			->innerJoin('postTags', 'postTags.tagId = tags.id')
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
		$query = $this->pdo->from($this->tableName)
			->select('COUNT(pt2.postId) AS postCount')
			->innerJoin('postTags pt1', 'pt1.tagId = tags.id')
			->innerJoin('postTags pt2', 'pt2.postId = pt1.postId')
			->where('pt2.tagId', $tagId)
			->groupBy('tags.id')
			->orderBy('postCount DESC, name ASC');

		$arrayEntities = array_filter(
			iterator_to_array($query),
			function($arrayEntity) use ($tagName)
			{
				return strcasecmp($arrayEntity['name'], $tagName) !== 0;
			});

		return $this->arrayToEntities($arrayEntities);
	}

	public function export()
	{
		$exported = [];
		foreach ($this->pdo->from($this->tableName) as $arrayEntity)
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
			$relations = iterator_to_array($this->pdo->from('tagRelations'));
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

			if (!isset($exported[$key1]) || !isset($exported[$key2]))
				continue;

			if (!isset($exported[$key1][$target]))
				$exported[$key1][$target] = [];

			$exported[$key1][$target][] = $exported[$key2]['name'];
		}

		return array_values($exported);
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

	protected function decorateQueryFromRequirement($query, Requirement $requirement)
	{
		if ($requirement->getType() === TagFilter::REQUIREMENT_PARTIAL_TAG_NAME)
		{
			$sql = 'INSTR(LOWER(tags.name), LOWER(?)) > 0';

			if ($requirement->isNegated())
				$sql = 'NOT ' . $sql;

			$query->where($sql, $requirement->getValue()->getValue());
			return;
		}

		elseif ($requirement->getType() === TagFilter::REQUIREMENT_CATEGORY)
		{
			$sql = 'IFNULL(category, \'default\')';
			$requirement->setType($sql);
			return parent::decorateQueryFromRequirement($query, $requirement);
		}

		parent::decorateQueryFromRequirement($query, $requirement);
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
		$relatedTagIds = array_filter(array_unique(array_map(
			function ($tag)
			{
				if (!$tag->getId())
					throw new \RuntimeException('Unsaved entities found');
				return $tag->getId();
			},
			$relatedTags)));

		$this->pdo->deleteFrom('tagRelations')
			->where('tag1id', $tag->getId())
			->where('type', $type)
			->execute();

		foreach ($relatedTagIds as $tagId)
		{
			$this->pdo
				->insertInto('tagRelations')
				->values([
					'tag1id' => $tag->getId(),
					'tag2id' => $tagId,
					'type' => $type])
				->execute();
		}
	}

	private function findRelatedTagsByType(Tag $tag, $type)
	{
		$tagId = $tag->getId();
		$query = $this->pdo->from($this->tableName)
			->innerJoin('tagRelations tr', 'tags.id = tr.tag2id')
			->where('tr.type', $type)
			->where('tr.tag1id', $tagId);
		$arrayEntities = iterator_to_array($query);
		return $this->arrayToEntities($arrayEntities);
	}
}
