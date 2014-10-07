<?php
namespace Szurubooru\Services;

class PostService
{
	private $config;
	private $validator;
	private $transactionManager;
	private $postDao;
	private $globalParamDao;
	private $timeService;
	private $authService;
	private $fileService;
	private $tagService;
	private $historyService;
	private $imageManipulator;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Validator $validator,
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Dao\PostDao $postDao,
		\Szurubooru\Dao\GlobalParamDao $globalParamDao,
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\TimeService $timeService,
		\Szurubooru\Services\FileService $fileService,
		\Szurubooru\Services\TagService $tagService,
		\Szurubooru\Services\HistoryService $historyService,
		\Szurubooru\Services\ImageManipulation\ImageManipulator $imageManipulator)
	{
		$this->config = $config;
		$this->validator = $validator;
		$this->transactionManager = $transactionManager;
		$this->postDao = $postDao;
		$this->globalParamDao = $globalParamDao;
		$this->timeService = $timeService;
		$this->authService = $authService;
		$this->fileService = $fileService;
		$this->tagService = $tagService;
		$this->historyService = $historyService;
		$this->imageManipulator = $imageManipulator;
	}

	public function getByNameOrId($postNameOrId)
	{
		$transactionFunc = function() use ($postNameOrId)
		{
			$post = $this->postDao->findByName($postNameOrId);
			if (!$post)
				$post = $this->postDao->findById($postNameOrId);
			if (!$post)
				throw new \InvalidArgumentException('Post with name "' . $postNameOrId . '" was not found.');
			return $post;
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getByName($postName)
	{
		$transactionFunc = function() use ($postName)
		{
			$post = $this->postDao->findByName($postName);
			if (!$post)
				throw new \InvalidArgumentException('Post with name "' . $postName . '" was not found.');
			return $post;
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getFiltered(\Szurubooru\SearchServices\Filters\PostFilter $filter)
	{
		$transactionFunc = function() use ($filter)
		{
			return $this->postDao->findFiltered($filter);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getFeatured()
	{
		$transactionFunc = function()
		{
			$globalParam = $this->globalParamDao->findByKey(\Szurubooru\Entities\GlobalParam::KEY_FEATURED_POST);
			if (!$globalParam)
				return null;
			return $this->getByNameOrId($globalParam->getValue());
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getHistory(\Szurubooru\Entities\Post $post)
	{
		$transactionFunc = function() use ($post)
		{
			$filter = new \Szurubooru\SearchServices\Filters\SnapshotFilter();

			$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
			$requirement->setType(\Szurubooru\SearchServices\Filters\SnapshotFilter::REQUIREMENT_PRIMARY_KEY);
			$requirement->setValue(new \Szurubooru\SearchServices\Requirements\RequirementSingleValue($post->getId()));
			$filter->addRequirement($requirement);

			$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
			$requirement->setType(\Szurubooru\SearchServices\Filters\SnapshotFilter::REQUIREMENT_TYPE);
			$requirement->setValue(new \Szurubooru\SearchServices\Requirements\RequirementSingleValue(\Szurubooru\Entities\Snapshot::TYPE_POST));
			$filter->addRequirement($requirement);

			return $this->historyService->getFiltered($filter)->getEntities();
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function createPost(\Szurubooru\FormData\UploadFormData $formData)
	{
		$transactionFunc = function() use ($formData)
		{
			$formData->validate($this->validator);

			$post = new \Szurubooru\Entities\Post();
			$post->setUploadTime($this->timeService->getCurrentTime());
			$post->setLastEditTime($this->timeService->getCurrentTime());
			$post->setUser($formData->anonymous ? null : $this->authService->getLoggedInUser());
			$post->setOriginalFileName($formData->contentFileName);
			$post->setName($this->getUniqueRandomPostName());

			$this->updatePostSafety($post, $formData->safety);
			$this->updatePostSource($post, $formData->source);
			$this->updatePostTags($post, $formData->tags);
			$this->updatePostContentFromStringOrUrl($post, $formData->content, $formData->url);

			$savedPost = $this->postDao->save($post);

			$this->historyService->saveSnapshot($this->historyService->getPostChangeSnapshot($savedPost));
			return $savedPost;
		};
		$ret = $this->transactionManager->commit($transactionFunc);
		$this->tagService->deleteUnusedTags();
		$this->tagService->exportJson();
		return $ret;
	}

	public function updatePost(\Szurubooru\Entities\Post $post, \Szurubooru\FormData\PostEditFormData $formData)
	{
		$transactionFunc = function() use ($post, $formData)
		{
			$this->validator->validate($formData);

			if ($post->getLastEditTime() !== $formData->seenEditTime)
				throw new \DomainException('Someone has already edited this post in the meantime.');

			$post->setLastEditTime($this->timeService->getCurrentTime());

			if ($formData->content !== null)
				$this->updatePostContentFromString($post, $formData->content);

			if ($formData->thumbnail !== null)
				$this->updatePostThumbnailFromString($post, $formData->thumbnail);

			if ($formData->safety !== null)
				$this->updatePostSafety($post, $formData->safety);

			if ($formData->source !== null)
				$this->updatePostSource($post, $formData->source);

			if ($formData->tags !== null)
				$this->updatePostTags($post, $formData->tags);

			if ($formData->relations !== null)
				$this->updatePostRelations($post, $formData->relations);

			$this->historyService->saveSnapshot($this->historyService->getPostChangeSnapshot($post));
			return $this->postDao->save($post);
		};
		$ret = $this->transactionManager->commit($transactionFunc);
		$this->tagService->deleteUnusedTags();
		$this->tagService->exportJson();
		return $ret;
	}

	private function updatePostSafety(\Szurubooru\Entities\Post $post, $newSafety)
	{
		$post->setSafety($newSafety);
	}

	private function updatePostSource(\Szurubooru\Entities\Post $post, $newSource)
	{
		$post->setSource($newSource);
	}

	private function updatePostContentFromStringOrUrl(\Szurubooru\Entities\Post $post, $content, $url)
	{
		if ($url)
			$this->updatePostContentFromUrl($post, $url);
		else if ($content)
			$this->updatePostContentFromString($post, $content);
		else
			throw new \DomainException('No content specified');
	}

	private function updatePostContentFromString(\Szurubooru\Entities\Post $post, $content)
	{
		if (!$content)
			throw new \DomainException('File cannot be empty.');

		if (strlen($content) > $this->config->database->maxPostSize)
			throw new \DomainException('Upload is too big.');

		$mime = \Szurubooru\Helpers\MimeHelper::getMimeTypeFromBuffer($content);
		$post->setContentMimeType($mime);

		if (\Szurubooru\Helpers\MimeHelper::isFlash($mime))
			$post->setContentType(\Szurubooru\Entities\Post::POST_TYPE_FLASH);
		elseif (\Szurubooru\Helpers\MimeHelper::isImage($mime))
			$post->setContentType(\Szurubooru\Entities\Post::POST_TYPE_IMAGE);
		elseif (\Szurubooru\Helpers\MimeHelper::isVideo($mime))
			$post->setContentType(\Szurubooru\Entities\Post::POST_TYPE_VIDEO);
		else
			throw new \DomainException('Unhandled file type: "' . $mime . '"');

		$post->setContentChecksum(sha1($content));
		$this->assertNoPostWithThisContentChecksum($post);

		$post->setContent($content);

		$image = $this->imageManipulator->loadFromBuffer($content);
		$post->setImageWidth($this->imageManipulator->getImageWidth($image));
		$post->setImageHeight($this->imageManipulator->getImageHeight($image));

		$post->setOriginalFileSize(strlen($content));
	}

	private function updatePostContentFromUrl(\Szurubooru\Entities\Post $post, $url)
	{
		if (!preg_match('/^https?:\/\//', $url))
			throw new \InvalidArgumentException('Invalid URL "' . $url . '"');

		$youtubeId = null;
		if (preg_match('/youtube.com\/watch.*?=([a-zA-Z0-9_-]+)/', $url, $matches))
			$youtubeId = $matches[1];

		if ($youtubeId)
		{
			$post->setContentType(\Szurubooru\Entities\Post::POST_TYPE_YOUTUBE);
			$post->setImageWidth(null);
			$post->setImageHeight(null);
			$post->setContentChecksum($url);
			$post->setOriginalFileName($url);
			$post->setOriginalFileSize(null);
			$post->setContentChecksum($youtubeId);

			$this->assertNoPostWithThisContentChecksum($post);
			$youtubeThumbnailUrl = 'http://img.youtube.com/vi/' . $youtubeId . '/mqdefault.jpg';
			$youtubeThumbnail = $this->fileService->download($youtubeThumbnailUrl);
			$post->setThumbnailSourceContent($youtubeThumbnail);
		}
		else
		{
			$contents = $this->fileService->download($url);
			$this->updatePostContentFromString($post, $contents);
		}
	}

	private function updatePostThumbnailFromString(\Szurubooru\Entities\Post $post, $newThumbnail)
	{
		if (strlen($newThumbnail) > $this->config->database->maxCustomThumbnailSize)
			throw new \DomainException('Thumbnail is too big.');

		$post->setThumbnailSourceContent($newThumbnail);
	}

	private function updatePostTags(\Szurubooru\Entities\Post $post, array $newTagNames)
	{
		$tags = [];
		foreach ($newTagNames as $tagName)
		{
			$tag = new \Szurubooru\Entities\Tag();
			$tag->setName($tagName);
			$tags[] = $tag;
		}
		$tags = $this->tagService->createTags($tags);
		$post->setTags($tags);
	}

	private function updatePostRelations(\Szurubooru\Entities\Post $post, array $newRelatedPostIds)
	{
		$relatedPosts = $this->postDao->findByIds($newRelatedPostIds);
		foreach ($newRelatedPostIds as $postId)
		{
			if (!isset($relatedPosts[$postId]))
				throw new \DomainException('Post with id "' . $postId . '" was not found.');
		}

		$post->setRelatedPosts($relatedPosts);
	}

	public function deletePost(\Szurubooru\Entities\Post $post)
	{
		$transactionFunc = function() use ($post)
		{
			$this->historyService->saveSnapshot($this->historyService->getPostDeleteSnapshot($post));
			$this->postDao->deleteById($post->getId());
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function featurePost(\Szurubooru\Entities\Post $post)
	{
		$transactionFunc = function() use ($post)
		{
			$previousFeaturedPost = $this->getFeatured();

			$post->setLastFeatureTime($this->timeService->getCurrentTime());
			$post->setFeatureCount($post->getFeatureCount() + 1);
			$this->postDao->save($post);
			$globalParam = new \Szurubooru\Entities\GlobalParam();
			$globalParam->setKey(\Szurubooru\Entities\GlobalParam::KEY_FEATURED_POST);
			$globalParam->setValue($post->getId());
			$this->globalParamDao->save($globalParam);

			if ($previousFeaturedPost)
				$this->historyService->saveSnapshot($this->historyService->getPostChangeSnapshot($previousFeaturedPost));
			$this->historyService->saveSnapshot($this->historyService->getPostChangeSnapshot($post));
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function updatePostGlobals()
	{
		$transactionFunc = function()
		{
			$countParam = new \Szurubooru\Entities\GlobalParam();
			$countParam->setKey(\Szurubooru\Entities\GlobalParam::KEY_POST_COUNT);
			$countParam->setValue($this->postDao->getCount());
			$this->globalParamDao->save($countParam);

			$fileSizeParam = new \Szurubooru\Entities\GlobalParam();
			$fileSizeParam->setKey(\Szurubooru\Entities\GlobalParam::KEY_POST_SIZE);
			$fileSizeParam->setValue($this->postDao->getTotalFileSize());
			$this->globalParamDao->save($fileSizeParam);
		};
		$this->transactionManager->commit($transactionFunc);
	}

	private function assertNoPostWithThisContentChecksum(\Szurubooru\Entities\Post $parent)
	{
		$checksumToCheck = $parent->getContentChecksum();
		$postWithThisChecksum = $this->postDao->findByContentChecksum($checksumToCheck);
		if ($postWithThisChecksum and $postWithThisChecksum->getId() !== $parent->getId())
			throw new \DomainException('Duplicate post: ' . $postWithThisChecksum->getIdMarkdown());
	}

	private function getRandomPostName()
	{
		return sha1(microtime(true) . mt_rand() . uniqid());
	}

	private function getUniqueRandomPostName()
	{
		while (true)
		{
			$name = $this->getRandomPostName();
			if (!$this->postDao->findByName($name))
				return $name;
		}
	}
}
