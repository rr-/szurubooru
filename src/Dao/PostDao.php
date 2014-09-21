<?php
namespace Szurubooru\Dao;

class PostDao extends AbstractDao implements ICrudDao
{
	private $tagDao;
	private $fileService;
	private $thumbnailService;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Dao\TagDao $tagDao,
		\Szurubooru\Services\FileService $fileService,
		\Szurubooru\Services\ThumbnailService $thumbnailService)
	{
		parent::__construct(
			$databaseConnection,
			'posts',
			new \Szurubooru\Dao\EntityConverters\PostEntityConverter());

		$this->tagDao = $tagDao;
		$this->fileService = $fileService;
		$this->thumbnailService = $thumbnailService;
	}

	public function findByName($name)
	{
		return $this->findOneBy('name', $name);
	}

	public function findByContentChecksum($checksum)
	{
		return $this->findOneBy('contentChecksum', $checksum);
	}

	protected function afterLoad(\Szurubooru\Entities\Entity $post)
	{
		$post->setLazyLoader(
			\Szurubooru\Entities\Post::LAZY_LOADER_CONTENT,
			function(\Szurubooru\Entities\Post $post)
			{
				return $this->fileService->load($post->getContentPath());
			});

		$post->setLazyLoader(
			\Szurubooru\Entities\Post::LAZY_LOADER_THUMBNAIL_SOURCE_CONTENT,
			function(\Szurubooru\Entities\Post $post)
			{
				return $this->fileService->load($post->getThumbnailSourceContentPath());
			});

		$post->setLazyLoader(
			\Szurubooru\Entities\Post::LAZY_LOADER_TAGS,
			function(\Szurubooru\Entities\Post $post)
			{
				return $this->getTags($post);
			});
	}

	protected function afterSave(\Szurubooru\Entities\Entity $post)
	{
		$this->syncContent($post);
		$this->syncThumbnailSourceContent($post);
		$this->syncTags($post);
	}

	private function getTags(\Szurubooru\Entities\Post $post)
	{
		return $this->tagDao->findByPostId($post->getId());
	}

	private function syncContent(\Szurubooru\Entities\Post $post)
	{
		$targetPath = $post->getContentPath();
		$content = $post->getContent();
		if ($content)
			$this->fileService->save($targetPath, $content);
		else
			$this->fileService->delete($targetPath, $content);
		$this->thumbnailService->deleteUsedThumbnails($targetPath);
	}

	private function syncThumbnailSourceContent(\Szurubooru\Entities\Post $post)
	{
		$targetPath = $post->getThumbnailSourceContentPath();
		$content = $post->getThumbnailSourceContent();
		if ($content)
			$this->fileService->save($targetPath, $content);
		else
			$this->fileService->delete($targetPath);
		$this->thumbnailService->deleteUsedThumbnails($targetPath);
	}

	private function syncTags(\Szurubooru\Entities\Post $post)
	{
		$tagNames = array_filter(array_unique(array_map(
			function ($tag)
			{
				return $tag->getName();
			},
			$post->getTags())));

		$this->tagDao->createMissingTags($tagNames);

		$tagIds = array_map(
			function($tag)
			{
				return $tag->getId();
			},
			$this->tagDao->findByNames($tagNames));

		$existingTagRelationIds = array_map(
			function($arrayEntity)
			{
				return $arrayEntity['tagId'];
			},
			iterator_to_array($this->fpdo->from('postTags')->where('postId', $post->getId())));

		$tagRelationsToInsert = array_diff($tagIds, $existingTagRelationIds);
		$tagRelationsToDelete = array_diff($existingTagRelationIds, $tagIds);

		foreach ($tagRelationsToInsert as $tagId)
		{
			$this->fpdo->insertInto('postTags')->values(['postId' => $post->getId(), 'tagId' => $tagId])->execute();
		}
		foreach ($tagRelationsToDelete as $tagId)
		{
			$this->fpdo->deleteFrom('postTags')->where('postId', $post->getId())->and('tagId', $tagId)->execute();
		}
	}
}
