<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\TagDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\Tag;
use Szurubooru\FormData\TagEditFormData;
use Szurubooru\SearchServices\Filters\TagFilter;
use Szurubooru\Services\TimeService;
use Szurubooru\Validator;

class TagService
{
	private $validator;
	private $transactionManager;
	private $postDao;
	private $tagDao;
	private $fileDao;
	private $timeService;
	private $historyService;

	public function __construct(
		Validator $validator,
		TransactionManager $transactionManager,
		PostDao $postDao,
		TagDao $tagDao,
		PublicFileDao $fileDao,
		HistoryService $historyService,
		TimeService $timeService)
	{
		$this->validator = $validator;
		$this->transactionManager = $transactionManager;
		$this->postDao = $postDao;
		$this->tagDao = $tagDao;
		$this->fileDao = $fileDao;
		$this->historyService = $historyService;
		$this->timeService = $timeService;
	}

	public function getByName($tagName)
	{
		$transactionFunc = function() use ($tagName)
		{
			$tag = $this->tagDao->findByName($tagName);
			if (!$tag)
				throw new \InvalidArgumentException('Tag with name "' . $tagName . '" was not found.');
			return $tag;
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getFiltered(TagFilter $filter)
	{
		$transactionFunc = function() use ($filter)
		{
			return $this->tagDao->findFiltered($filter);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getSiblings($tagName)
	{
		$transactionFunc = function() use ($tagName)
		{
			return $this->tagDao->findSiblings($tagName);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function exportJson()
	{
		$tags = [];
		foreach ($this->tagDao->findAll() as $tag)
		{
			$tags[$tag->getId()] = [
				'name' => $tag->getName(),
				'usages' => $tag->getUsages()];
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

	public function updateTag(Tag $tag, TagEditFormData $formData)
	{
		$transactionFunc = function() use ($tag, $formData)
		{
			$this->validator->validate($formData);

			if ($formData->name !== null)
				$this->updateTagName($tag, $formData->name);

			return $this->tagDao->save($tag);
		};
		$ret = $this->transactionManager->commit($transactionFunc);

		$transactionFunc = function() use ($tag)
		{
			$posts = $this->postDao->findByTagName($tag->getName());
			foreach ($posts as $post)
				$this->historyService->saveSnapshot($this->historyService->getPostChangeSnapshot($post));
		};
		$this->transactionManager->commit($transactionFunc);

		$this->exportJson();
		return $ret;
	}

	private function updateTagName(Tag $tag, $newName)
	{
		$tag->setName($newName);
	}
}
