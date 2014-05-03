<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class PostModel extends AbstractCrudModel
{
	protected static $config;

	public static function getTableName()
	{
		return 'post';
	}

	public static function init()
	{
		self::$config = getConfig();
	}

	public static function spawn()
	{
		$post = new PostEntity;
		$post->score = 0;
		$post->favCount = 0;
		$post->commentCount = 0;
		$post->safety = PostSafety::Safe;
		$post->hidden = false;
		$post->uploadDate = time();
		do
		{
			$post->name = md5(mt_rand() . uniqid());
		}
		while (file_exists($post->getFullPath()));
		return $post;
	}

	public static function save($post)
	{
		Database::transaction(function() use ($post)
		{
			self::forgeId($post);

			$bindings = [
				'type' => $post->type,
				'name' => $post->name,
				'orig_name' => $post->origName,
				'file_hash' => $post->fileHash,
				'file_size' => $post->fileSize,
				'mime_type' => $post->mimeType,
				'safety' => $post->safety,
				'hidden' => $post->hidden,
				'upload_date' => $post->uploadDate,
				'image_width' => $post->imageWidth,
				'image_height' => $post->imageHeight,
				'uploader_id' => $post->uploaderId,
				'source' => $post->source,
				];

			$stmt = new Sql\UpdateStatement();
			$stmt->setTable('post');

			foreach ($bindings as $key => $value)
				$stmt->setColumn($key, new Sql\Binding($value));

			$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($post->id)));
			Database::exec($stmt);

			//tags
			$tags = $post->getTags();

			$stmt = new Sql\DeleteStatement();
			$stmt->setTable('post_tag');
			$stmt->setCriterion(new Sql\EqualsFunctor('post_id', new Sql\Binding($post->id)));
			Database::exec($stmt);

			foreach ($tags as $postTag)
			{
				$stmt = new Sql\InsertStatement();
				$stmt->setTable('post_tag');
				$stmt->setColumn('post_id', new Sql\Binding($post->id));
				$stmt->setColumn('tag_id', new Sql\Binding($postTag->id));
				Database::exec($stmt);
			}

			//relations
			$relations = $post->getRelations();

			$stmt = new Sql\DeleteStatement();
			$stmt->setTable('crossref');
			$binding = new Sql\Binding($post->id);
			$stmt->setCriterion((new Sql\DisjunctionFunctor)
				->add(new Sql\EqualsFunctor('post_id', $binding))
				->add(new Sql\EqualsFunctor('post2_id', $binding)));
			Database::exec($stmt);

			foreach ($relations as $relatedPost)
			{
				$stmt = new Sql\InsertStatement();
				$stmt->setTable('crossref');
				$stmt->setColumn('post_id', new Sql\Binding($post->id));
				$stmt->setColumn('post2_id', new Sql\Binding($relatedPost->id));
				Database::exec($stmt);
			}
		});
	}

	public static function remove($post)
	{
		Database::transaction(function() use ($post)
		{
			$binding = new Sql\Binding($post->id);

			$stmt = new Sql\DeleteStatement();
			$stmt->setTable('post_score');
			$stmt->setCriterion(new Sql\EqualsFunctor('post_id', $binding));
			Database::exec($stmt);

			$stmt->setTable('post_tag');
			Database::exec($stmt);

			$stmt->setTable('favoritee');
			Database::exec($stmt);

			$stmt->setTable('comment');
			Database::exec($stmt);

			$stmt->setTable('crossref');
			$stmt->setCriterion((new Sql\DisjunctionFunctor)
				->add(new Sql\EqualsFunctor('post_id', $binding))
				->add(new Sql\EqualsFunctor('post_id', $binding)));
			Database::exec($stmt);

			$stmt->setTable('post');
			$stmt->setCriterion(new Sql\EqualsFunctor('id', $binding));
			Database::exec($stmt);
		});
	}




	public static function findByName($key, $throw = true)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable('post');
		$stmt->setCriterion(new Sql\EqualsFunctor('name', new Sql\Binding($key)));

		$row = Database::fetchOne($stmt);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid post name "%s"', $key);
		return null;
	}

	public static function findByIdOrName($key, $throw = true)
	{
		if (is_numeric($key))
			$post = self::findById($key, $throw);
		else
			$post = self::findByName($key, $throw);
		return $post;
	}

	public static function findByHash($key, $throw = true)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable('post');
		$stmt->setCriterion(new Sql\EqualsFunctor('file_hash', new Sql\Binding($key)));

		$row = Database::fetchOne($stmt);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid post hash "%s"', $hash);
		return null;
	}



	public static function preloadComments($posts)
	{
		if (empty($posts))
			return;

		$postMap = [];
		$tagsMap = [];
		foreach ($posts as $post)
		{
			$postId = $post->id;
			$postMap[$postId] = $post;
			$commentMap[$postId] = [];
		}
		$postIds = array_unique(array_keys($postMap));

		$stmt = new Sql\SelectStatement();
		$stmt->setTable('comment');
		$stmt->addColumn('comment.*');
		$stmt->addColumn('post_id');
		$stmt->setCriterion(Sql\InFunctor::fromArray('post_id', Sql\Binding::fromArray($postIds)));
		$rows = Database::fetchAll($stmt);

		foreach ($rows as $row)
		{
			if (isset($comments[$row['id']]))
				continue;
			unset($row['post_id']);
			$comment = CommentModel::convertRow($row);
			$comments[$row['id']] = $comment;
		}

		foreach ($rows as $row)
		{
			$postId = $row['post_id'];
			$commentMap[$postId] []= $comments[$row['id']];
		}

		foreach ($commentMap as $postId => $comments)
			$postMap[$postId]->setCache('comments', $comments);
	}

	public static function preloadTags($posts)
	{
		if (empty($posts))
			return;

		$postMap = [];
		$tagsMap = [];
		foreach ($posts as $post)
		{
			$postId = $post->id;
			$postMap[$postId] = $post;
			$tagsMap[$postId] = [];
		}
		$postIds = array_unique(array_keys($postMap));

		$stmt = new Sql\SelectStatement();
		$stmt->setTable('tag');
		$stmt->addColumn('tag.*');
		$stmt->addColumn('post_id');
		$stmt->addInnerJoin('post_tag', new Sql\EqualsFunctor('post_tag.tag_id', 'tag.id'));
		$stmt->setCriterion(Sql\InFunctor::fromArray('post_id', Sql\Binding::fromArray($postIds)));
		$rows = Database::fetchAll($stmt);

		foreach ($rows as $row)
		{
			if (isset($tags[$row['id']]))
				continue;
			unset($row['post_id']);
			$tag = TagModel::convertRow($row);
			$tags[$row['id']] = $tag;
		}

		foreach ($rows as $row)
		{
			$postId = $row['post_id'];
			$tagsMap[$postId] []= $tags[$row['id']];
		}

		foreach ($tagsMap as $postId => $tags)
			$postMap[$postId]->setCache('tags', $tags);
	}



	public static function validateSafety($safety)
	{
		$safety = intval($safety);

		if (!in_array($safety, PostSafety::getAll()))
			throw new SimpleException('Invalid safety type "%s"', $safety);

		return $safety;
	}

	public static function validateSource($source)
	{
		$source = trim($source);

		$maxLength = 200;
		if (strlen($source) > $maxLength)
			throw new SimpleException('Source must have at most %d characters', $maxLength);

		return $source;
	}

	public static function validateThumbSize($width, $height)
	{
		$width = $width === null ? self::$config->browsing->thumbWidth : $width;
		$height = $height === null ? self::$config->browsing->thumbHeight : $height;
		$width = min(1000, max(1, $width));
		$height = min(1000, max(1, $height));
		return [$width, $height];
	}

	private static function getThumbPathTokenized($text, $name, $width = null, $height = null)
	{
		list ($width, $height) = self::validateThumbSize($width, $height);

		return TextHelper::absolutePath(TextHelper::replaceTokens($text, [
			'fullpath' => self::$config->main->thumbsPath . DS . $name,
			'width' => $width,
			'height' => $height]));
	}

	public static function getThumbCustomPath($name, $width = null, $height = null)
	{
		return self::getThumbPathTokenized('{fullpath}.custom', $name, $width, $height);
	}

	public static function getThumbDefaultPath($name, $width = null, $height = null)
	{
		return self::getThumbPathTokenized('{fullpath}-{width}x{height}.default', $name, $width, $height);
	}

	public static function getFullPath($name)
	{
		return TextHelper::absolutePath(self::$config->main->filesPath . DS . $name);
	}
}

PostModel::init();
