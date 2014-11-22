<?php
namespace Szurubooru\Services;
use Szurubooru\Config;
use Szurubooru\Dao\GlobalParamDao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\GlobalParam;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Tag;
use Szurubooru\FormData\PostEditFormData;
use Szurubooru\FormData\UploadFormData;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\Helpers\TypeHelper;
use Szurubooru\Search\Filters\PostFilter;
use Szurubooru\Search\Filters\SnapshotFilter;
use Szurubooru\Search\Requirements\Requirement;
use Szurubooru\Search\Requirements\RequirementCompositeValue;
use Szurubooru\Search\Requirements\RequirementSingleValue;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PostHistoryService;
use Szurubooru\Services\ImageConverter;
use Szurubooru\Services\ImageManipulation\ImageManipulator;
use Szurubooru\Services\NetworkingService;
use Szurubooru\Services\TagService;
use Szurubooru\Services\TimeService;
use Szurubooru\Validator;

class PostService
{
	private $config;
	private $validator;
	private $transactionManager;
	private $postDao;
	private $globalParamDao;
	private $timeService;
	private $authService;
	private $networkingService;
	private $tagService;
	private $postHistoryService;
	private $imageConverter;
	private $imageManipulator;

	public function __construct(
		Config $config,
		Validator $validator,
		TransactionManager $transactionManager,
		PostDao $postDao,
		GlobalParamDao $globalParamDao,
		AuthService $authService,
		TimeService $timeService,
		NetworkingService $networkingService,
		TagService $tagService,
		PostHistoryService $postHistoryService,
		ImageConverter $imageConverter,
		ImageManipulator $imageManipulator)
	{
		$this->config = $config;
		$this->validator = $validator;
		$this->transactionManager = $transactionManager;
		$this->postDao = $postDao;
		$this->globalParamDao = $globalParamDao;
		$this->timeService = $timeService;
		$this->authService = $authService;
		$this->networkingService = $networkingService;
		$this->tagService = $tagService;
		$this->postHistoryService = $postHistoryService;
		$this->imageConverter = $imageConverter;
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

	public function getFiltered(PostFilter $filter)
	{
		$transactionFunc = function() use ($filter)
		{
			return $this->postDao->findFiltered($filter);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function createPost(UploadFormData $formData)
	{
		$transactionFunc = function() use ($formData)
		{
			$formData->validate($this->validator);

			$post = new Post();
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

			$this->postHistoryService->savePostCreation($savedPost);
			return $savedPost;
		};
		$ret = $this->transactionManager->commit($transactionFunc);
		$this->tagService->exportJson();
		return $ret;
	}

	public function updatePost(Post $post, PostEditFormData $formData)
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

			if (count($formData->flags) > 0)
				$this->updatePostFlags($post, $formData->flags);

			$this->postHistoryService->savePostChange($post);
			return $this->postDao->save($post);
		};
		$ret = $this->transactionManager->commit($transactionFunc);
		$this->tagService->exportJson();
		return $ret;
	}

	private function updatePostSafety(Post $post, $newSafety)
	{
		$post->setSafety($newSafety);
	}

	private function updatePostSource(Post $post, $newSource)
	{
		$post->setSource($newSource);
	}

	private function updatePostContentFromStringOrUrl(Post $post, $content, $url)
	{
		if ($url)
			$this->updatePostContentFromUrl($post, $url);
		else if ($content)
			$this->updatePostContentFromString($post, $content);
		else
			throw new \DomainException('No content specified');
	}

	private function updatePostContentFromString(Post $post, $content)
	{
		if (!$content)
			throw new \DomainException('File cannot be empty.');

		if (strlen($content) > $this->config->database->maxPostSize)
			throw new \DomainException('Upload is too big.');

		$mime = MimeHelper::getMimeTypeFromBuffer($content);
		$post->setContentMimeType($mime);

		if (MimeHelper::isFlash($mime))
			$post->setContentType(Post::POST_TYPE_FLASH);
		elseif (MimeHelper::isImage($mime))
			$post->setContentType(Post::POST_TYPE_IMAGE);
		elseif (MimeHelper::isVideo($mime))
			$post->setContentType(Post::POST_TYPE_VIDEO);
		else
			throw new \DomainException('Unhandled file type: "' . $mime . '"');

		$post->setContentChecksum(sha1($content));
		$this->assertNoPostWithThisContentChecksum($post);

		$post->setContent($content);

		try
		{
			$image = $this->imageConverter->createImageFromBuffer($content);
			$post->setImageWidth($this->imageManipulator->getImageWidth($image));
			$post->setImageHeight($this->imageManipulator->getImageHeight($image));
		}
		catch (\Exception $e)
		{
			$post->setImageWidth(null);
			$post->setImageHeight(null);
		}

		$post->setOriginalFileSize(strlen($content));
	}

	private function updatePostContentFromUrl(Post $post, $url)
	{
		if (!preg_match('/^https?:\/\//', $url))
			throw new \InvalidArgumentException('Invalid URL "' . $url . '"');

		$youtubeId = null;
		if (preg_match('/youtube.com\/watch.*?=([a-zA-Z0-9_-]+)/', $url, $matches))
			$youtubeId = $matches[1];

		if ($youtubeId)
		{
			$post->setContentType(Post::POST_TYPE_YOUTUBE);
			$post->setImageWidth(null);
			$post->setImageHeight(null);
			$post->setContentChecksum($url);
			$post->setOriginalFileName($url);
			$post->setOriginalFileSize(null);
			$post->setContentChecksum($youtubeId);

			$this->assertNoPostWithThisContentChecksum($post);
			$youtubeThumbnailUrl = 'http://img.youtube.com/vi/' . $youtubeId . '/mqdefault.jpg';
			$youtubeThumbnail = $this->networkingService->download($youtubeThumbnailUrl);
			$post->setThumbnailSourceContent($youtubeThumbnail);
		}
		else
		{
			$contents = $this->networkingService->download($url);
			$this->updatePostContentFromString($post, $contents);
		}
	}

	private function updatePostThumbnailFromString(Post $post, $newThumbnail)
	{
		if (strlen($newThumbnail) > $this->config->database->maxCustomThumbnailSize)
			throw new \DomainException('Thumbnail is too big.');

		$post->setThumbnailSourceContent($newThumbnail);
	}

	private function updatePostTags(Post $post, array $newTagNames)
	{
		$tags = [];
		foreach ($newTagNames as $tagName)
		{
			$tag = new Tag();
			$tag->setName($tagName);
			$tags[] = $tag;
		}
		$tags = $this->tagService->createTags($tags);
		foreach ($tags as $tag)
		{
			if ($tag->isBanned())
				throw new \DomainException('Cannot use banned tag "' . $tag->getName() . '"');
		}
		$post->setTags($tags);
	}

	private function updatePostRelations(Post $post, array $newRelatedPostIds)
	{
		$relatedPosts = $this->postDao->findByIds($newRelatedPostIds);
		foreach ($newRelatedPostIds as $postId)
		{
			if (!isset($relatedPosts[$postId]))
				throw new \DomainException('Post with id "' . $postId . '" was not found.');
		}

		$post->setRelatedPosts($relatedPosts);
	}

	private function updatePostFlags(Post $post, \StdClass $flags)
	{
		$value = 0;
		if (!empty($flags->loop))
			$value |= Post::FLAG_LOOP;
		$post->setFlags($value);
	}

	public function deletePost(Post $post)
	{
		$transactionFunc = function() use ($post)
		{
			$this->postHistoryService->savePostDeletion($post);
			$this->postDao->deleteById($post->getId());
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function updatePostGlobals()
	{
		$transactionFunc = function()
		{
			$countParam = new GlobalParam();
			$countParam->setKey(GlobalParam::KEY_POST_COUNT);
			$countParam->setValue($this->postDao->getCount());
			$this->globalParamDao->save($countParam);

			$fileSizeParam = new GlobalParam();
			$fileSizeParam->setKey(GlobalParam::KEY_POST_SIZE);
			$fileSizeParam->setValue($this->postDao->getTotalFileSize());
			$this->globalParamDao->save($fileSizeParam);
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function decorateFilterFromBrowsingSettings(PostFilter $filter)
	{
		$currentUser = $this->authService->getLoggedInUser();
		$userSettings = $currentUser->getBrowsingSettings();
		if (!$userSettings)
			return;

		if (!empty($userSettings->listPosts) && !count($filter->getRequirementsByType(PostFilter::REQUIREMENT_SAFETY)))
		{
			$values = [];
			if (!TypeHelper::toBool($userSettings->listPosts->safe))
				$values[] = Post::POST_SAFETY_SAFE;
			if (!TypeHelper::toBool($userSettings->listPosts->sketchy))
				$values[] = Post::POST_SAFETY_SKETCHY;
			if (!TypeHelper::toBool($userSettings->listPosts->unsafe))
				$values[] = Post::POST_SAFETY_UNSAFE;
			if (count($values))
			{
				$requirementValue = new RequirementCompositeValue();
				$requirementValue->setValues($values);
				$requirement = new Requirement();
				$requirement->setType(PostFilter::REQUIREMENT_SAFETY);
				$requirement->setValue($requirementValue);
				$requirement->setNegated(true);
				$filter->addRequirement($requirement);
			}
		}

		if (!empty($userSettings->hideDownvoted) && !count($filter->getRequirementsByType(PostFilter::REQUIREMENT_USER_SCORE)))
		{
			$requirementValue = new RequirementCompositeValue();
			$requirementValue->setValues([$currentUser->getName(), -1]);
			$requirement = new Requirement();
			$requirement->setType(PostFilter::REQUIREMENT_USER_SCORE);
			$requirement->setValue($requirementValue);
			$requirement->setNegated(true);
			$filter->addRequirement($requirement);
		}
	}

	private function assertNoPostWithThisContentChecksum(Post $parent)
	{
		$checksumToCheck = $parent->getContentChecksum();
		$postWithThisChecksum = $this->postDao->findByContentChecksum($checksumToCheck);
		if ($postWithThisChecksum && $postWithThisChecksum->getId() !== $parent->getId())
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
