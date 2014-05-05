<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class PostEntity extends AbstractEntity implements IValidatable
{
	protected $type;
	protected $name;
	public $origName;
	public $fileHash;
	public $fileSize;
	public $mimeType;
	protected $safety;
	public $hidden;
	public $uploadDate;
	public $imageWidth;
	public $imageHeight;
	public $uploaderId;
	public $source;
	public $commentCount = 0;
	public $favCount = 0;
	public $score = 0;

	public function validate()
	{
		//todo
		if (empty($this->getType()))
			throw new SimpleException('No post type detected');
	}

	public function getUploader()
	{
		if ($this->hasCache('uploader'))
			return $this->getCache('uploader');
		$uploader = UserModel::findById($this->uploaderId, false);
		$this->setCache('uploader', $uploader);
		return $uploader;
	}

	public function setUploader($user)
	{
		$this->uploaderId = $user->getId();
		$this->setCache('uploader', $user);
	}

	public function getComments()
	{
		if ($this->hasCache('comments'))
			return $this->getCache('comments');
		$comments = CommentModel::findAllByPostId($this->getId());
		$this->setCache('comments', $comments);
		return $comments;
	}

	public function getFavorites()
	{
		if ($this->hasCache('favoritee'))
			return $this->getCache('favoritee');
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('user.*');
		$stmt->setTable('user');
		$stmt->addInnerJoin('favoritee', new Sql\EqualsFunctor('favoritee.user_id', 'user.id'));
		$stmt->setCriterion(new Sql\EqualsFunctor('favoritee.post_id', new Sql\Binding($this->getId())));
		$rows = Database::fetchAll($stmt);
		$favorites = UserModel::convertRows($rows);
		$this->setCache('favoritee', $favorites);
		return $favorites;
	}

	public function getRelations()
	{
		if ($this->hasCache('relations'))
			return $this->getCache('relations');

		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('post.*');
		$stmt->setTable('post');
		$binding = new Sql\Binding($this->getId());
		$stmt->addInnerJoin('crossref', (new Sql\DisjunctionFunctor)
			->add(
				(new Sql\ConjunctionFunctor)
					->add(new Sql\EqualsFunctor('post.id', 'crossref.post2_id'))
					->add(new Sql\EqualsFunctor('crossref.post_id', $binding)))
			->add(
				(new Sql\ConjunctionFunctor)
					->add(new Sql\EqualsFunctor('post.id', 'crossref.post_id'))
					->add(new Sql\EqualsFunctor('crossref.post2_id', $binding))));
		$rows = Database::fetchAll($stmt);
		$posts = PostModel::convertRows($rows);
		$this->setCache('relations', $posts);
		return $posts;
	}

	public function setRelations(array $relations)
	{
		foreach ($relations as $relatedPost)
			if (!$relatedPost->getId())
				throw new Exception('All related posts must be saved');
		$uniqueRelations = [];
		foreach ($relations as $relatedPost)
			$uniqueRelations[$relatedPost->getId()] = $relatedPost;
		$relations = array_values($uniqueRelations);
		$this->setCache('relations', $relations);
	}

	public function setRelationsFromText($relationsText)
	{
		$config = getConfig();
		$relatedIds = array_filter(preg_split('/\D/', $relationsText));

		$relatedPosts = [];
		foreach ($relatedIds as $relatedId)
		{
			if ($relatedId == $this->getId())
				continue;

			if (count($relatedPosts) > $config->browsing->maxRelatedPosts)
			{
				throw new SimpleException(
					'Too many related posts (maximum: %d)',
					$config->browsing->maxRelatedPosts);
			}

			$relatedPosts []= PostModel::findById($relatedId);
		}

		$this->setRelations($relatedPosts);
	}

	public function getTags()
	{
		if ($this->hasCache('tags'))
			return $this->getCache('tags');
		$tags = TagModel::findAllByPostId($this->getId());
		$this->setCache('tags', $tags);
		return $tags;
	}

	public function setTags(array $tags)
	{
		foreach ($tags as $tag)
			if (!$tag->getId())
				throw new Exception('All tags must be saved');
		$uniqueTags = [];
		foreach ($tags as $tag)
			$uniqueTags[$tag->getId()] = $tag;
		$tags = array_values($uniqueTags);
		$this->setCache('tags', $tags);
	}

	public function setTagsFromText($tagsText)
	{
		$tagNames = TagModel::validateTags($tagsText);
		$tags = [];
		foreach ($tagNames as $tagName)
		{
			$tag = TagModel::findByName($tagName, false);
			if (!$tag)
			{
				$tag = TagModel::spawn();
				$tag->setName($tagName);
				TagModel::save($tag);
			}
			$tags []= $tag;
		}
		$this->setTags($tags);
	}

	public function isTaggedWith($tagName)
	{
		$tagName = trim(strtolower($tagName));
		foreach ($this->getTags() as $tag)
			if (trim(strtolower($tag->getName())) == $tagName)
				return true;
		return false;
	}

	public function setHidden($hidden)
	{
		$this->hidden = boolval($hidden);
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setType(PostType $type)
	{
		$type->validate();
		$this->type = $type;
	}

	public function getSafety()
	{
		return $this->safety;
	}

	public function setSafety(PostSafety $safety)
	{
		$safety->validate();
		$this->safety = $safety;
	}

	public function setSource($source)
	{
		$this->source = PostModel::validateSource($source);
	}

	public function getThumbCustomPath($width = null, $height = null)
	{
		return PostModel::getThumbCustomPath($this->getName(), $width, $height);
	}

	public function getThumbDefaultPath($width = null, $height = null)
	{
		return PostModel::getThumbDefaultPath($this->getName(), $width, $height);
	}

	public function getFullPath()
	{
		return PostModel::getFullPath($this->getName());
	}

	public function hasCustomThumb($width = null, $height = null)
	{
		$thumbPath = $this->getThumbCustomPath($width, $height);
		return file_exists($thumbPath);
	}

	public function setCustomThumbnailFromPath($srcPath)
	{
		$config = getConfig();

		$mimeType = mime_content_type($srcPath);
		if (!in_array($mimeType, ['image/gif', 'image/png', 'image/jpeg']))
			throw new SimpleException('Invalid thumbnail type "%s"', $mimeType);

		list ($imageWidth, $imageHeight) = getimagesize($srcPath);
		if ($imageWidth != $config->browsing->thumbWidth
			or $imageHeight != $config->browsing->thumbHeight)
		{
			throw new SimpleException(
				'Invalid thumbnail size (should be %dx%d)',
				$config->browsing->thumbWidth,
				$config->browsing->thumbHeight);
		}

		$dstPath = $this->getThumbCustomPath();

		TransferHelper::moveUpload($srcPath, $dstPath);
	}

	public function generateThumb($width = null, $height = null)
	{
		list ($width, $height) = PostModel::validateThumbSize($width, $height);
		$srcPath = $this->getFullPath();
		$dstPath = $this->getThumbDefaultPath($width, $height);

		if ($this->getType()->toInteger() == PostType::Youtube)
		{
			return ThumbnailHelper::generateFromUrl(
				'http://img.youtube.com/vi/' . $this->fileHash . '/mqdefault.jpg',
				$dstPath,
				$width,
				$height);
		}
		else
		{
			return ThumbnailHelper::generateFromPath($srcPath, $dstPath, $width, $height);
		}
	}

	public function setContentFromPath($srcPath, $origName)
	{
		$this->fileSize = filesize($srcPath);
		$this->fileHash = md5_file($srcPath);
		$this->origName = $origName;

		if ($this->fileSize == 0)
			throw new SimpleException('Specified file is empty');

		$this->mimeType = mime_content_type($srcPath);
		switch ($this->mimeType)
		{
			case 'image/gif':
			case 'image/png':
			case 'image/jpeg':
				list ($imageWidth, $imageHeight) = getimagesize($srcPath);
				$this->setType(new PostType(PostType::Image));
				$this->imageWidth = $imageWidth;
				$this->imageHeight = $imageHeight;
				break;
			case 'application/x-shockwave-flash':
				list ($imageWidth, $imageHeight) = getimagesize($srcPath);
				$this->setType(new PostType(PostType::Flash));
				$this->imageWidth = $imageWidth;
				$this->imageHeight = $imageHeight;
				break;
			case 'video/webm':
			case 'video/mp4':
			case 'video/ogg':
			case 'application/ogg':
			case 'video/x-flv':
			case 'video/3gpp':
				list ($imageWidth, $imageHeight) = getimagesize($srcPath);
				$this->setType(new PostType(PostType::Video));
				$this->imageWidth = $imageWidth;
				$this->imageHeight = $imageHeight;
				break;
			default:
				throw new SimpleException('Invalid file type "%s"', $this->mimeType);
		}

		$duplicatedPost = PostModel::findByHash($this->fileHash, false);
		if ($duplicatedPost !== null and (!$this->getId() or $this->getId() != $duplicatedPost->getId()))
		{
			throw new SimpleException(
				'Duplicate upload: %s',
				TextHelper::reprPost($duplicatedPost));
		}

		$dstPath = $this->getFullPath();

		TransferHelper::moveUpload($srcPath, $dstPath);

		$thumbPath = $this->getThumbDefaultPath();
		if (file_exists($thumbPath))
			unlink($thumbPath);
	}

	public function setContentFromUrl($srcUrl)
	{
		if (!preg_match('/^https?:\/\//', $srcUrl))
			throw new SimpleException('Invalid URL "%s"', $srcUrl);

		$this->origName = $srcUrl;

		if (preg_match('/youtube.com\/watch.*?=([a-zA-Z0-9_-]+)/', $srcUrl, $matches))
		{
			$youtubeId = $matches[1];
			$this->setType(new PostType(PostType::Youtube));
			$this->mimeType = null;
			$this->fileSize = null;
			$this->fileHash = $youtubeId;
			$this->imageWidth = null;
			$this->imageHeight = null;

			$thumbPath = $this->getThumbDefaultPath();
			if (file_exists($thumbPath))
				unlink($thumbPath);

			$duplicatedPost = PostModel::findByHash($youtubeId, false);
			if ($duplicatedPost !== null and (!$this->getId() or $this->getId() != $duplicatedPost->getId()))
			{
				throw new SimpleException(
					'Duplicate upload: %s',
					TextHelper::reprPost($duplicatedPost));
			}
			return;
		}

		$srcPath = tempnam(sys_get_temp_dir(), 'upload') . '.dat';

		try
		{
			$maxBytes = TextHelper::stripBytesUnits(ini_get('upload_max_filesize'));

			TransferHelper::download($srcUrl, $srcPath, $maxBytes);

			$this->setContentFromPath($srcPath, basename($srcUrl));
		}
		finally
		{
			if (file_exists($srcPath))
				unlink($srcPath);
		}
	}

	public function getEditToken()
	{
		$x = [];
		foreach ($this->getTags() as $tag)
			$x []= TextHelper::reprTag($tag->getName());
		foreach ($this->getRelations() as $relatedPost)
			$x []= TextHelper::reprPost($relatedPost);
		$x []= $this->getSafety()->toInteger();
		$x []= $this->source;
		$x []= $this->fileHash;
		natcasesort($x);
		$x = join(' ', $x);
		return md5($x);
	}
}
