<?php
namespace Szurubooru\Services;

class PostService
{
	private $config;
	private $validator;
	private $transactionManager;
	private $postDao;
	private $postSearchService;
	private $timeService;
	private $authService;
	private $fileService;
	private $imageManipulator;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Validator $validator,
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Dao\PostDao $postDao,
		\Szurubooru\Dao\Services\PostSearchService $postSearchService,
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\TimeService $timeService,
		\Szurubooru\Services\FileService $fileService,
		\Szurubooru\Services\ImageManipulation\ImageManipulator $imageManipulator)
	{
		$this->config = $config;
		$this->validator = $validator;
		$this->transactionManager = $transactionManager;
		$this->postDao = $postDao;
		$this->postSearchService = $postSearchService;
		$this->timeService = $timeService;
		$this->authService = $authService;
		$this->fileService = $fileService;
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

	public function getFiltered(\Szurubooru\FormData\SearchFormData $formData)
	{
		$transactionFunc = function() use ($formData)
		{
			$this->validator->validate($formData);
			$searchFilter = new \Szurubooru\Dao\SearchFilter($this->config->posts->postsPerPage, $formData);
			return $this->postSearchService->getFiltered($searchFilter);
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

			return $this->postDao->save($post);
		};
		return $this->transactionManager->commit($transactionFunc);
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

	private function updatePostTags(\Szurubooru\Entities\Post $post, array $newTagNames)
	{
		$tags = [];
		foreach ($newTagNames as $tagName)
		{
			$tag = new \Szurubooru\Entities\Tag();
			$tag->setName($tagName);
			$tags[] = $tag;
		}
		$post->setTags($tags);
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
