<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\PostEntityConverter;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Dao\TagDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Post;
use Szurubooru\SearchServices\Filters\PostFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\Services\ThumbnailService;

class PostDao extends AbstractDao implements ICrudDao
{
	private $tagDao;
	private $userDao;
	private $fileDao;
	private $thumbnailService;

	public function __construct(
		DatabaseConnection $databaseConnection,
		TagDao $tagDao,
		UserDao $userDao,
		PublicFileDao $fileDao,
		ThumbnailService $thumbnailService)
	{
		parent::__construct(
			$databaseConnection,
			'posts',
			new PostEntityConverter());

		$this->tagDao = $tagDao;
		$this->userDao = $userDao;
		$this->fileDao = $fileDao;
		$this->thumbnailService = $thumbnailService;
	}

	public function getCount()
	{
		return count($this->pdo->from($this->tableName));
	}

	public function getTotalFileSize()
	{
		$query = $this->pdo->from($this->tableName)->select('SUM(originalFileSize) AS __sum');
		return intval(iterator_to_array($query)[0]['__sum']);
	}

	public function findByName($name)
	{
		return $this->findOneBy('name', $name);
	}

	public function findByTagName($tagName)
	{
		$query = $this->pdo->from('posts')
			->innerJoin('postTags', 'postTags.postId = posts.id')
			->innerJoin('tags', 'postTags.tagId = tags.id')
			->where('tags.name', $tagName);
		$arrayEntities = iterator_to_array($query);
		return $this->arrayToEntities($arrayEntities);
	}

	public function findByContentChecksum($checksum)
	{
		return $this->findOneBy('contentChecksum', $checksum);
	}

	protected function afterLoad(Entity $post)
	{
		$post->setLazyLoader(
			Post::LAZY_LOADER_CONTENT,
			function (Post $post)
			{
				return $this->fileDao->load($post->getContentPath());
			});

		$post->setLazyLoader(
			Post::LAZY_LOADER_THUMBNAIL_SOURCE_CONTENT,
			function (Post $post)
			{
				return $this->fileDao->load($post->getThumbnailSourceContentPath());
			});

		$post->setLazyLoader(
			Post::LAZY_LOADER_USER,
			function (Post $post)
			{
				return $this->getUser($post);
			});

		$post->setLazyLoader(
			Post::LAZY_LOADER_TAGS,
			function (Post $post)
			{
				return $this->getTags($post);
			});

		$post->setLazyLoader(
			Post::LAZY_LOADER_RELATED_POSTS,
			function (Post $post)
			{
				return $this->getRelatedPosts($post);
			});
	}

	protected function afterSave(Entity $post)
	{
		$this->syncContent($post);
		$this->syncThumbnailSourceContent($post);
		$this->syncTags($post);
		$this->syncPostRelations($post);
	}

	protected function decorateQueryFromRequirement($query, Requirement $requirement)
	{
		if ($requirement->getType() === PostFilter::REQUIREMENT_TAG)
		{
			$tagName = $requirement->getValue()->getValue();
			$tag = $this->tagDao->findByName($tagName);
			if (!$tag)
				throw new \DomainException('Invalid tag: "' . $tagName . '"');

			$sql = 'EXISTS (
				SELECT 1 FROM postTags
				WHERE postTags.postId = posts.id
					AND postTags.tagId = ?)';

			if ($requirement->isNegated())
				$sql = 'NOT ' . $sql;

			$query->where($sql, $tag->getId());
			return;
		}

		elseif ($requirement->getType() === PostFilter::REQUIREMENT_FAVORITE)
		{
			$query->innerJoin('favorites _fav', '_fav.postId = posts.id');
			$query->innerJoin('users favoritedBy', 'favoritedBy.id = _fav.userId');
		}

		elseif ($requirement->getType() === PostFilter::REQUIREMENT_COMMENT)
		{
			$query->innerJoin('comments _comment', '_comment.postId = posts.id');
			$query->innerJoin('users commentedBy', 'commentedBy.id = _comment.userId');
		}

		elseif ($requirement->getType() === PostFilter::REQUIREMENT_UPLOADER)
		{
			$query->innerJoin('users uploader', 'uploader.id = posts.userId');
		}

		elseif ($requirement->getType() === PostFilter::REQUIREMENT_USER_SCORE)
		{
			$values = $requirement->getValue()->getValues();
			$userName = $values[0];
			$score = $values[1];
			$sql = 'EXISTS (
				SELECT 1 FROM scores
				INNER JOIN users ON scores.userId = users.id
				WHERE scores.postId = posts.id
					AND LOWER(users.name) = LOWER(?)
					AND scores.score = ?)';
			if ($requirement->isnegated())
				$sql = 'NOT ' . $sql;
			$query->where($sql, $userName, $score);
			return;
		}

		parent::decorateQueryFromRequirement($query, $requirement);
	}

	private function getTags(Post $post)
	{
		return $this->tagDao->findByPostId($post->getId());
	}

	private function getUser(Post $post)
	{
		return $this->userDao->findById($post->getUserId());
	}

	private function getRelatedPosts(Post $post)
	{
		$relatedPostIds = [];

		foreach ($this->pdo->from('postRelations')->where('post1id', $post->getId()) as $arrayEntity)
		{
			$postId = intval($arrayEntity['post2id']);
			if ($postId !== $post->getId())
				$relatedPostIds[] = $postId;
		}

		foreach ($this->pdo->from('postRelations')->where('post2id', $post->getId()) as $arrayEntity)
		{
			$postId = intval($arrayEntity['post1id']);
			if ($postId !== $post->getId())
				$relatedPostIds[] = $postId;
		}

		return $this->findByIds($relatedPostIds);
	}

	private function syncContent(Post $post)
	{
		$targetPath = $post->getContentPath();
		$content = $post->getContent();
		if ($content)
			$this->fileDao->save($targetPath, $content);
		else
			$this->fileDao->delete($targetPath, $content);
		$this->thumbnailService->deleteUsedThumbnails($targetPath);
	}

	private function syncThumbnailSourceContent(Post $post)
	{
		$targetPath = $post->getThumbnailSourceContentPath();
		$content = $post->getThumbnailSourceContent();
		if ($content)
			$this->fileDao->save($targetPath, $content);
		else
			$this->fileDao->delete($targetPath);
		$this->thumbnailService->deleteUsedThumbnails($targetPath);
	}

	private function syncTags(Post $post)
	{
		$tagIds = array_map(
			function ($tag)
			{
				if (!$tag->getId())
					throw new \RuntimeException('Unsaved entities found');
				return $tag->getId();
			},
			$post->getTags());

		$existingTagRelationIds = array_map(
			function ($arrayEntity)
			{
				return $arrayEntity['tagId'];
			},
			iterator_to_array($this->pdo->from('postTags')->where('postId', $post->getId())));

		$tagRelationsToInsert = array_diff($tagIds, $existingTagRelationIds);
		$tagRelationsToDelete = array_diff($existingTagRelationIds, $tagIds);

		foreach ($tagRelationsToInsert as $tagId)
		{
			$this->pdo->insertInto('postTags')->values(['postId' => $post->getId(), 'tagId' => $tagId])->execute();
		}
		foreach ($tagRelationsToDelete as $tagId)
		{
			$this->pdo->deleteFrom('postTags')->where('postId', $post->getId())->where('tagId', $tagId)->execute();
		}
	}

	private function syncPostRelations(Post $post)
	{
		$this->pdo->deleteFrom('postRelations')->where('post1id', $post->getId())->execute();
		$this->pdo->deleteFrom('postRelations')->where('post2id', $post->getId())->execute();

		$relatedPostIds = array_filter(array_unique(array_map(
			function ($post)
			{
				if (!$post->getId())
					throw new \RuntimeException('Unsaved entities found');
				return $post->getId();
			},
			$post->getRelatedPosts())));

		foreach ($relatedPostIds as $postId)
		{
			$this->pdo
				->insertInto('postRelations')
				->values([
					'post1id' => $post->getId(),
					'post2id' => $postId])
				->execute();
		}
	}
}
