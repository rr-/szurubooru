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

			$query = (new SqlQuery)
				->update('post')
				->set(join(', ', array_map(function($key) { return $key . ' = ?'; }, array_keys($bindings))))
				->put(array_values($bindings))
				->where('id = ?')->put($post->id);
			Database::query($query);

			//tags
			$tags = $post->getTags();

			$query = (new SqlQuery)
				->deleteFrom('post_tag')
				->where('post_id = ?')->put($post->id);
			Database::query($query);

			foreach ($tags as $postTag)
			{
				$query = (new SqlQuery)
					->insertInto('post_tag')
					->surround('post_id, tag_id')
					->values()->surround('?, ?')
					->put([$post->id, $postTag->id]);
				Database::query($query);
			}

			//relations
			$relations = $post->getRelations();

			$query = (new SqlQuery)
				->deleteFrom('crossref')
				->where('post_id = ?')->put($post->id)
				->or('post2_id = ?')->put($post->id);
			Database::query($query);

			foreach ($relations as $relatedPost)
			{
				$query = (new SqlQuery)
					->insertInto('crossref')
					->surround('post_id, post2_id')
					->values()->surround('?, ?')
					->put([$post->id, $relatedPost->id]);
				Database::query($query);
			}
		});
	}

	public static function remove($post)
	{
		Database::transaction(function() use ($post)
		{
			$queries = [];

			$queries []= (new SqlQuery)
				->deleteFrom('post_score')
				->where('post_id = ?')->put($post->id);

			$queries []= (new SqlQuery)
				->deleteFrom('post_tag')
				->where('post_id = ?')->put($post->id);

			$queries []= (new SqlQuery)
				->deleteFrom('crossref')
				->where('post_id = ?')->put($post->id)
				->or('post2_id = ?')->put($post->id);

			$queries []= (new SqlQuery)
				->deleteFrom('favoritee')
				->where('post_id = ?')->put($post->id);

			$queries []= (new SqlQuery)
				->update('comment')
				->set('post_id = NULL')
				->where('post_id = ?')->put($post->id);

			$queries []= (new SqlQuery)
				->deleteFrom('post')
				->where('id = ?')->put($post->id);

			foreach ($queries as $query)
				Database::query($query);
		});
	}




	public static function findByName($key, $throw = true)
	{
		$query = (new SqlQuery)
			->select('*')
			->from('post')
			->where('name = ?')->put($key);

		$row = Database::fetchOne($query);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleException('Invalid post name "' . $key . '"');
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
		$query = (new SqlQuery)
			->select('*')
			->from('post')
			->where('file_hash = ?')->put($key);

		$row = Database::fetchOne($query);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleException('Invalid post hash "' . $hash . '"');
		return null;
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
		$postIds = array_keys($postMap);

		$sqlQuery = (new SqlQuery)
			->select('tag.*, post_id')
			->from('tag')
			->innerJoin('post_tag')->on('post_tag.tag_id = tag.id')
			->where('post_id')->in()->genSlots($postIds)->put($postIds);
		$rows = Database::fetchAll($sqlQuery);

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
		{
			$postMap[$postId]->setCache('tags', $tags);
		}
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
