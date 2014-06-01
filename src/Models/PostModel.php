<?php
use \Chibi\Sql as Sql;

final class PostModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'post';
	}

	protected static function saveSingle($post)
	{
		$post->validate();

		Core::getDatabase()->transaction(function() use ($post)
		{
			self::forgeId($post);

			$bindings = [
				'type' => $post->getType()->toInteger(),
				'name' => $post->getName(),
				'orig_name' => $post->getOriginalName(),
				'file_hash' => $post->getFileHash(),
				'file_size' => $post->getFileSize(),
				'mime_type' => $post->getMimeType(),
				'safety' => $post->getSafety()->toInteger(),
				'hidden' => $post->isHidden() ? 1 : 0,
				'upload_date' => $post->getCreationTime(),
				'image_width' => $post->getImageWidth(),
				'image_height' => $post->getImageHeight(),
				'uploader_id' => $post->getUploaderId(),
				'source' => $post->getSource(),
				];

			$stmt = Sql\Statements::update();
			$stmt->setTable('post');

			foreach ($bindings as $key => $value)
				$stmt->setColumn($key, new Sql\Binding($value));

			$stmt->setCriterion(Sql\Functors::equals('id', new Sql\Binding($post->getId())));
			Core::getDatabase()->execute($stmt);

			//tags
			$tags = $post->getTags();

			$stmt = Sql\Statements::delete();
			$stmt->setTable('post_tag');
			$stmt->setCriterion(Sql\Functors::equals('post_id', new Sql\Binding($post->getId())));
			Core::getDatabase()->execute($stmt);

			foreach ($tags as $postTag)
			{
				$stmt = Sql\Statements::insert();
				$stmt->setTable('post_tag');
				$stmt->setColumn('post_id', new Sql\Binding($post->getId()));
				$stmt->setColumn('tag_id', new Sql\Binding($postTag->getId()));
				Core::getDatabase()->execute($stmt);
			}

			//relations
			$relations = $post->getRelations();

			$stmt = Sql\Statements::delete();
			$stmt->setTable('crossref');
			$binding = new Sql\Binding($post->getId());
			$stmt->setCriterion(Sql\Functors::disjunction()
				->add(Sql\Functors::equals('post_id', $binding))
				->add(Sql\Functors::equals('post2_id', $binding)));
			Core::getDatabase()->execute($stmt);

			foreach ($relations as $relatedPost)
			{
				$stmt = Sql\Statements::insert();
				$stmt->setTable('crossref');
				$stmt->setColumn('post_id', new Sql\Binding($post->getId()));
				$stmt->setColumn('post2_id', new Sql\Binding($relatedPost->getId()));
				Core::getDatabase()->execute($stmt);
			}
		});

		return $post;
	}

	protected static function removeSingle($post)
	{
		Core::getDatabase()->transaction(function() use ($post)
		{
			$binding = new Sql\Binding($post->getId());

			$stmt = Sql\Statements::delete();
			$stmt->setTable('post_score');
			$stmt->setCriterion(Sql\Functors::equals('post_id', $binding));
			Core::getDatabase()->execute($stmt);

			$stmt->setTable('post_tag');
			Core::getDatabase()->execute($stmt);

			$stmt->setTable('favoritee');
			Core::getDatabase()->execute($stmt);

			$stmt->setTable('comment');
			Core::getDatabase()->execute($stmt);

			$stmt->setTable('crossref');
			$stmt->setCriterion(Sql\Functors::disjunction()
				->add(Sql\Functors::equals('post_id', $binding))
				->add(Sql\Functors::equals('post_id', $binding)));
			Core::getDatabase()->execute($stmt);

			$stmt->setTable('post');
			$stmt->setCriterion(Sql\Functors::equals('id', $binding));
			Core::getDatabase()->execute($stmt);
		});
	}




	public static function getByName($key)
	{
		$ret = self::tryGetByName($key);
		if (!$ret)
			throw new SimpleNotFoundException('Invalid post name "%s"', $key);
		return $ret;
	}

	public static function tryGetByName($key, $throw = true)
	{
		$stmt = Sql\Statements::select();
		$stmt->setColumn('*');
		$stmt->setTable('post');
		$stmt->setCriterion(Sql\Functors::equals('name', new Sql\Binding($key)));

		$row = Core::getDatabase()->fetchOne($stmt);
		return $row
			? self::spawnFromDatabaseRow($row)
			: null;
	}

	public static function getByIdOrName($key)
	{
		if (is_numeric($key))
			$post = self::getById($key);
		else
			$post = self::getByName($key);
		return $post;
	}

	public static function getByHash($key)
	{
		$ret = self::tryGetByHash($key);
		if (!$ret)
			throw new SimpleNotFoundException('Invalid post hash "%s"', $hash);
		return $ret;
	}

	public static function tryGetByHash($key)
	{
		$stmt = Sql\Statements::select();
		$stmt->setColumn('*');
		$stmt->setTable('post');
		$stmt->setCriterion(Sql\Functors::equals('file_hash', new Sql\Binding($key)));

		$row = Core::getDatabase()->fetchOne($stmt);
		return $row
			? self::spawnFromDatabaseRow($row)
			: null;
	}



	public static function preloadComments($posts)
	{
		if (empty($posts))
			return;

		$postMap = [];
		$tagsMap = [];
		foreach ($posts as $post)
		{
			$postId = $post->getId();
			$postMap[$postId] = $post;
			$commentMap[$postId] = [];
		}
		$postIds = array_unique(array_keys($postMap));

		$stmt = Sql\Statements::select();
		$stmt->setTable('comment');
		$stmt->addColumn('comment.*');
		$stmt->addColumn('post_id');
		$stmt->setCriterion(Sql\Functors::in('post_id', Sql\Binding::fromArray($postIds)));
		$rows = Core::getDatabase()->fetchAll($stmt);

		foreach ($rows as $row)
		{
			if (isset($comments[$row['id']]))
				continue;
			$comment = CommentModel::spawnFromDatabaseRow($row);
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
			$postId = $post->getId();
			$postMap[$postId] = $post;
			$tagsMap[$postId] = [];
		}
		$postIds = array_unique(array_keys($postMap));

		$stmt = Sql\Statements::select();
		$stmt->setTable('tag');
		$stmt->addColumn('tag.*');
		$stmt->addColumn('post_id');
		$stmt->addInnerJoin('post_tag', Sql\Functors::equals('post_tag.tag_id', 'tag.id'));
		$stmt->setCriterion(Sql\Functors::in('post_id', Sql\Binding::fromArray($postIds)));
		$rows = Core::getDatabase()->fetchAll($stmt);

		foreach ($rows as $row)
		{
			if (isset($tags[$row['id']]))
				continue;
			unset($row['post_id']);
			$tag = TagModel::spawnFromDatabaseRow($row);
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



	public static function getSpaceUsage()
	{
		$unixTime = PropertyModel::get(PropertyModel::PostSpaceUsageUnixTime);
		if (($unixTime !== null) and (time() - $unixTime < 24 * 60 * 60))
			return PropertyModel::get(PropertyModel::PostSpaceUsage);

		$totalBytes = 0;
		$paths = [Core::getConfig()->main->filesPath, Core::getConfig()->main->thumbnailsPath];

		foreach ($paths as $path)
		{
			$iterator =
				new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator(
					$path, FilesystemIterator::SKIP_DOTS));

			foreach ($iterator as $object)
				$totalBytes += $object->getSize();
		}

		PropertyModel::set(PropertyModel::PostSpaceUsage, $totalBytes);
		PropertyModel::set(PropertyModel::PostSpaceUsageUnixTime, time());

		return $totalBytes;
	}

	public static function getFeaturedPost()
	{
		$featuredPostId = PropertyModel::get(PropertyModel::FeaturedPostId);
		if (!$featuredPostId)
			return null;
		return PostModel::tryGetById($featuredPostId);
	}

	public static function featureRandomPostIfNecessary()
	{
		$config = Core::getConfig();
		$featuredPostRotationTime = $config->misc->featuredPostMaxDays * 24 * 3600;

		$featuredPostId = PropertyModel::get(PropertyModel::FeaturedPostId);
		$featuredPostUnixTime = PropertyModel::get(PropertyModel::FeaturedPostUnixTime);

		//check if too old
		if (!$featuredPostId or $featuredPostUnixTime + $featuredPostRotationTime < time())
		{
			self::featureRandomPost();
			return true;
		}

		//check if post was deleted
		$featuredPost = PostModel::tryGetById($featuredPostId);
		if (!$featuredPost)
		{
			self::featureRandomPost();
			return true;
		}

		return false;
	}

	public static function featureRandomPost()
	{
		$stmt = Sql\Statements::select()
			->setColumn('id')
			->setTable('post')
			->setCriterion(Sql\Functors::conjunction()
				->add(Sql\Functors::negation(new Sql\StringExpression('hidden')))
				->add(Sql\Functors::equals('type', new Sql\Binding(PostType::Image)))
				->add(Sql\Functors::equals('safety', new Sql\Binding(PostSafety::Safe))))
			->setOrderBy(Sql\Functors::random(), Sql\Statements\SelectStatement::ORDER_DESC);
		$featuredPostId = Core::getDatabase()->fetchOne($stmt)['id'];
		if (!$featuredPostId)
			return null;

		PropertyModel::set(PropertyModel::FeaturedPostId, $featuredPostId);
		PropertyModel::set(PropertyModel::FeaturedPostUnixTime, time());
		PropertyModel::set(PropertyModel::FeaturedPostUserName, null);
	}
}
