<?php
class PostModel extends AbstractCrudModel
{
	protected static $config;

	public static function getTableName()
	{
		return 'post';
	}

	public static function init()
	{
		self::$config = \Chibi\Registry::getConfig();
	}

	public static function spawn()
	{
		$post = new PostEntity;
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

			$stmt = new SqlUpdateStatement();
			$stmt->setTable('post');

			foreach ($bindings as $key => $value)
				$stmt->setColumn($key, new SqlBinding($value));

			$stmt->setCriterion(new SqlEqualsOperator('id', new SqlBinding($post->id)));
			Database::exec($stmt);

			//tags
			$tags = $post->getTags();

			$stmt = new SqlDeleteStatement();
			$stmt->setTable('post_tag');
			$stmt->setCriterion(new SqlEqualsOperator('post_id', new SqlBinding($post->id)));
			Database::exec($stmt);

			foreach ($tags as $postTag)
			{
				$stmt = new SqlInsertStatement();
				$stmt->setTable('post_tag');
				$stmt->setColumn('post_id', new SqlBinding($post->id));
				$stmt->setColumn('tag_id', new SqlBinding($postTag->id));
				Database::exec($stmt);
			}

			//relations
			$relations = $post->getRelations();

			$stmt = new SqlDeleteStatement();
			$stmt->setTable('crossref');
			$binding = new SqlBinding($post->id);
			$stmt->setCriterion((new SqlDisjunction)
				->add(new SqlEqualsOperator('post_id', $binding))
				->add(new SqlEqualsOperator('post2_id', $binding)));
			Database::exec($stmt);

			foreach ($relations as $relatedPost)
			{
				$stmt = new SqlInsertStatement();
				$stmt->setTable('crossref');
				$stmt->setColumn('post_id', new SqlBinding($post->id));
				$stmt->setColumn('post2_id', new SqlBinding($relatedPost->id));
				Database::exec($stmt);
			}
		});
	}

	public static function remove($post)
	{
		Database::transaction(function() use ($post)
		{
			$binding = new SqlBinding($post->id);

			$stmt = new SqlDeleteStatement();
			$stmt->setTable('post_score');
			$stmt->setCriterion(new SqlEqualsOperator('post_id', $binding));
			Database::exec($stmt);

			$stmt->setTable('post_tag');
			Database::exec($stmt);

			$stmt->setTable('favoritee');
			Database::exec($stmt);

			$stmt->setTable('comment');
			Database::exec($stmt);

			$stmt->setTable('crossref');
			$stmt->setCriterion((new SqlDisjunction)
				->add(new SqlEqualsOperator('post_id', $binding))
				->add(new SqlEqualsOperator('post_id', $binding)));
			Database::exec($stmt);

			$stmt->setTable('post');
			$stmt->setCriterion(new SqlEqualsOperator('id', $binding));
			Database::exec($stmt);
		});
	}




	public static function findByName($key, $throw = true)
	{
		$stmt = new SqlSelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable('post');
		$stmt->setCriterion(new SqlEqualsOperator('name', new SqlBinding($key)));

		$row = Database::fetchOne($stmt);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid post name "' . $key . '"');
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
		$stmt = new SqlSelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable('post');
		$stmt->setCriterion(new SqlEqualsOperator('file_hash', new SqlBinding($key)));

		$row = Database::fetchOne($stmt);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid post hash "' . $hash . '"');
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

		$stmt = new SqlSelectStatement();
		$stmt->setTable('comment');
		$stmt->addColumn('comment.*');
		$stmt->addColumn('post_id');
		$stmt->setCriterion(SqlInOperator::fromArray('post_id', SqlBinding::fromArray($postIds)));
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

		$stmt = new SqlSelectStatement();
		$stmt->setTable('tag');
		$stmt->addColumn('tag.*');
		$stmt->addColumn('post_id');
		$stmt->addInnerJoin('post_tag', new SqlEqualsOperator('post_tag.tag_id', 'tag.id'));
		$stmt->setCriterion(SqlInOperator::fromArray('post_id', SqlBinding::fromArray($postIds)));
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
			throw new SimpleException('Invalid safety type "' . $safety . '"');

		return $safety;
	}

	public static function validateSource($source)
	{
		$source = trim($source);

		$maxLength = 200;
		if (strlen($source) > $maxLength)
			throw new SimpleException('Source must have at most ' . $maxLength . ' characters');

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
