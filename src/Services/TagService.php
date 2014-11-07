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
	private $postHistoryService;
	private $tagHistoryService;

	public function __construct(
		Validator $validator,
		TransactionManager $transactionManager,
		PostDao $postDao,
		TagDao $tagDao,
		PublicFileDao $fileDao,
		PostHistoryService $postHistoryService,
		TagHistoryService $tagHistoryService,
		TimeService $timeService)
	{
		$this->validator = $validator;
		$this->transactionManager = $transactionManager;
		$this->postDao = $postDao;
		$this->tagDao = $tagDao;
		$this->fileDao = $fileDao;
		$this->postHistoryService = $postHistoryService;
		$this->tagHistoryService = $tagHistoryService;
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
		$exported = $this->tagDao->export();
		$json = json_encode($exported);
		$this->fileDao->save('tags.json', $json);
	}

	public function createTags(array $tags)
	{
		$transactionFunc = function() use ($tags)
		{
			$tagNameGetter = function($tag)
				{
					return strtolower(is_string($tag) ? $tag : $tag->getName());
				};

			$tagsNotToCreate = [];
			$tagNames = array_map($tagNameGetter, $tags);
			$tagNames = array_filter(array_unique($tagNames));
			foreach ($this->tagDao->findByNames($tagNames) as $tag)
				$tagsNotToCreate[$tagNameGetter($tag)] = $tag;

			$tagsToCreate = [];
			foreach ($tags as $key => $tag)
			{
				if (isset($tagsNotToCreate[$tagNameGetter($tag)]))
					continue;

				if (is_string($tag))
				{
					$tagName = $tag;
					$tag = new Tag();
					$tag->setName($tagName);
				}
				$tag->setCreationTime($this->timeService->getCurrentTime());
				$tagsToCreate[$tagNameGetter($tag)] = $tag;
			}

			$createdTags = [];
			foreach ($this->tagDao->batchSave($tagsToCreate) as $tag)
			{
				$createdTags[$tagNameGetter($tag)] = $tag;
				$this->tagHistoryService->saveTagChange($tag);
			}

			$result = [];
			foreach ($tags as $key => $tag)
			{
				$result[$key] =
					isset($tagsToCreate[$tagNameGetter($tag)])
						? $createdTags[$tagNameGetter($tag)]
						: $tagsNotToCreate[$tagNameGetter($tag)];
			}
			return $result;
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function updateTag(Tag $tag, TagEditFormData $formData)
	{
		$oldName = $tag->getName();

		$transactionFunc = function() use ($tag, $formData)
		{
			$this->validator->validate($formData);

			if ($formData->name !== null)
				$this->updateTagName($tag, $formData->name);

			if ($formData->category !== null)
				$this->updateTagCategory($tag, $formData->category);

			if ($formData->banned !== $tag->isBanned())
				$tag->setBanned(boolval($formData->banned));

			if ($formData->implications !== null)
				$this->updateImplications($tag, $formData->implications);

			if ($formData->suggestions !== null)
				$this->updateSuggestions($tag, $formData->suggestions);

			$this->tagHistoryService->saveTagChange($tag);
			return $this->tagDao->save($tag);
		};
		$ret = $this->transactionManager->commit($transactionFunc);

		if ($oldName !== $tag->getName())
		{
			$transactionFunc = function() use ($tag)
			{
				$posts = $this->postDao->findByTagName($tag->getName());
				foreach ($posts as $post)
					$this->postHistoryService->savePostChange($post);
			};
			$this->transactionManager->commit($transactionFunc);
		}

		$this->exportJson();
		return $ret;
	}

	public function deleteTag(Tag $tag)
	{
		if ($tag->getUsages() !== 0)
			throw new \DomainException('Only tags with no usages can be deleted.');

		$transactionFunc = function() use ($tag)
		{
			$this->tagDao->deleteById($tag->getId());
			$this->tagHistoryService->saveTagDeletion($tag);
		};

		$this->transactionManager->commit($transactionFunc);
	}

	private function updateTagName(Tag $tag, $newName)
	{
		$otherTag = $this->tagDao->findByName($newName);
		if ($otherTag and $otherTag->getId() !== $tag->getId())
			throw new \DomainException('Tag with this name already exists.');
		$tag->setName($newName);
	}

	private function updateTagCategory(Tag $tag, $newCategory)
	{
		if ($newCategory === 'default')
			$tag->setCategory($newCategory);
		else
			$tag->setCategory($newCategory);
	}

	private function updateImplications(Tag $tag, array $relatedNames)
	{
		$relatedNames = array_udiff($relatedNames, [$tag->getName()], 'strcasecmp');
		$tag->setImpliedTags($this->createTags($relatedNames));
	}

	private function updateSuggestions(Tag $tag, array $relatedNames)
	{
		$relatedNames = array_udiff($relatedNames, [$tag->getName()], 'strcasecmp');
		$tag->setSuggestedTags($this->createTags($relatedNames));
	}
}
