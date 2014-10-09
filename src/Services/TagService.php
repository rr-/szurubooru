<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Dao\TagDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\Tag;
use Szurubooru\SearchServices\Filters\TagFilter;
use Szurubooru\Services\TimeService;

class TagService
{
	private $transactionManager;
	private $tagDao;
	private $fileDao;
	private $timeService;

	public function __construct(
		TransactionManager $transactionManager,
		TagDao $tagDao,
		PublicFileDao $fileDao,
		TimeService $timeService)
	{
		$this->transactionManager = $transactionManager;
		$this->tagDao = $tagDao;
		$this->fileDao = $fileDao;
		$this->timeService = $timeService;
	}

	public function getFiltered(TagFilter $filter)
	{
		$transactionFunc = function() use ($filter)
		{
			return $this->tagDao->findFiltered($filter);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function exportJson()
	{
		$tags = [];
		foreach ($this->tagDao->findAll() as $tag)
		{
			$tags[$tag->getName()] = $tag->getUsages();
		}
		$json = json_encode($tags);
		$this->fileDao->save('tags.json', $json);
	}

	public function deleteUnusedTags()
	{
		$transactionFunc = function()
		{
			$this->tagDao->deleteUnused();
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function createTags(array $tags)
	{
		$transactionFunc = function() use ($tags)
		{
			$tagNameGetter = function($tag)
				{
					return strtolower($tag->getName());
				};

			$tagNames = array_map($tagNameGetter, $tags);
			$tagNames = array_filter(array_unique($tagNames));

			$tagsNotToCreate = $this->tagDao->findByNames($tagNames);
			$tagNamesNotToCreate = array_map($tagNameGetter, $tagsNotToCreate);
			$tagNamesToCreate = array_udiff($tagNames, $tagNamesNotToCreate, 'strcasecmp');

			$tagsToCreate = [];
			foreach ($tagNamesToCreate as $tagName)
			{
				$tag = new Tag;
				$tag->setName($tagName);
				$tag->setCreationTime($this->timeService->getCurrentTime());
				$tagsToCreate[] = $tag;
			}
			$createdTags = $this->tagDao->batchSave($tagsToCreate);

			$tagsNotToCreate = array_combine($tagNamesNotToCreate, $tagsNotToCreate);
			$createdTags = array_combine($tagNamesToCreate, $createdTags);
			$result = [];
			foreach ($tags as $key => $tag)
			{
				if (isset($tagsNotToCreate[$tagNameGetter($tag)]))
					$tag = $tagsNotToCreate[$tagNameGetter($tag)];
				else
					$tag = $createdTags[$tagNameGetter($tag)];
				$result[$key] = $tag;
			}
			return $result;
		};
		return $this->transactionManager->commit($transactionFunc);
	}
}
