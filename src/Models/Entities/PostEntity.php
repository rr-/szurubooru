<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

final class PostEntity extends AbstractEntity implements IValidatable
{
	private $type;
	private $name;
	private $origName;
	private $fileHash;
	private $fileSize;
	private $mimeType;
	private $safety;
	private $hidden;
	private $uploadDate;
	private $imageWidth;
	private $imageHeight;
	private $uploaderId;
	private $source;

	public function fillNew()
	{
		$this->setSafety(new PostSafety(PostSafety::Safe));
		$this->setHidden(false);
		$this->setCreationTime(time());
		do
		{
			$this->setName(md5(mt_rand() . uniqid()));
		}
		while (file_exists($this->getFullPath()));
	}

	public function fillFromDatabase($row)
	{
		$this->id = (int) $row['id'];
		$this->name = $row['name'];
		$this->origName = $row['orig_name'];
		$this->fileHash = $row['file_hash'];
		$this->fileSize = (int) $row['file_size'];
		$this->mimeType = $row['mime_type'];
		$this->hidden = (bool) $row['hidden'];
		$this->uploadDate = $row['upload_date'];
		$this->imageWidth = (int) $row['image_width'];
		$this->imageHeight = (int) $row['image_height'];
		$this->uploaderId = (int) $row['uploader_id'];
		$this->source = $row['source'];
		$this->setCache('comment_count', $row['comment_count']);
		$this->setCache('fav_count', $row['fav_count']);
		$this->setCache('score', $row['score']);
		$this->setType(new PostType($row['type']));
		$this->setSafety(new PostSafety($row['safety']));
	}

	public function validate()
	{
		if (empty($this->getType()))
			throw new SimpleException('No post type detected');

		if (empty($this->getTags()))
			throw new SimpleException('No tags set');

		$this->getType()->validate();

		$this->getSafety()->validate();

		$maxSourceLength = getConfig()->posts->maxSourceLength;
		if (strlen($this->getSource()) > $maxSourceLength)
			throw new SimpleException('Source must have at most %d characters', $maxSourceLength);
	}

	public function getUploader()
	{
		if ($this->hasCache('uploader'))
			return $this->getCache('uploader');
		if (!$this->uploaderId)
			return null;
		$uploader = UserModel::tryGetById($this->uploaderId);
		$this->setCache('uploader', $uploader);
		return $uploader;
	}

	public function getUploaderId()
	{
		return $this->uploaderId;
	}

	public function setUploader($user)
	{
		$this->uploaderId = $user !== null ? $user->getId() : null;
		$this->setCache('uploader', $user);
	}

	public function getComments()
	{
		if ($this->hasCache('comments'))
			return $this->getCache('comments');
		$comments = CommentModel::getAllByPostId($this->getId());
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
		$favorites = UserModel::spawnFromDatabaseRows($rows);
		$this->setCache('favoritee', $favorites);
		return $favorites;
	}

	public function getScore()
	{
		return (int) $this->getColumnWithCache('score');
	}

	public function getCommentCount()
	{
		return (int) $this->getColumnWithCache('comment_count');
	}

	public function getFavoriteCount()
	{
		return (int) $this->getColumnWithCache('fav_count');
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
		$posts = PostModel::spawnFromDatabaseRows($rows);
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

	public function getTags()
	{
		if ($this->hasCache('tags'))
			return $this->getCache('tags');
		$tags = TagModel::getAllByPostId($this->getId());
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

	public function isTaggedWith($tagName)
	{
		$tagName = trim(strtolower($tagName));
		foreach ($this->getTags() as $tag)
			if (trim(strtolower($tag->getName())) == $tagName)
				return true;
		return false;
	}

	public function isHidden()
	{
		return $this->hidden;
	}

	public function setHidden($hidden)
	{
		$this->hidden = boolval($hidden);
	}

	public function getCreationTime()
	{
		return $this->uploadDate;
	}

	public function setCreationTime($unixTime)
	{
		$this->uploadDate = $unixTime;
	}

	public function getImageWidth()
	{
		return $this->imageWidth;
	}

	public function setImageWidth($imageWidth)
	{
		$this->imageWidth = $imageWidth;
	}

	public function getImageHeight()
	{
		return $this->imageHeight;
	}

	public function setImageHeight($imageHeight)
	{
		$this->imageHeight = $imageHeight;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getOriginalName()
	{
		return $this->origName;
	}

	public function setOriginalName($origName)
	{
		$this->origName = $origName;
	}

	public function getFileHash()
	{
		return $this->fileHash;
	}

	public function setFileHash($fileHash)
	{
		$this->fileHash = $fileHash;
	}

	public function getFileSize()
	{
		return $this->fileSize;
	}

	public function setFileSize($fileSize)
	{
		$this->fileSize = $fileSize;
	}

	public function getMimeType()
	{
		return $this->mimeType;
	}

	public function setMimeType($mimeType)
	{
		$this->mimeType = $mimeType;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setType(PostType $type)
	{
		$this->type = $type;
	}

	public function getSafety()
	{
		return $this->safety;
	}

	public function setSafety(PostSafety $safety)
	{
		$this->safety = $safety;
	}

	public function getSource()
	{
		return $this->source;
	}

	public function setSource($source)
	{
		$this->source = $source === null ? null : trim($source);
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

		TransferHelper::copy($srcPath, $dstPath);
	}

	public function generateThumb($width = null, $height = null)
	{
		list ($width, $height) = PostModel::validateThumbSize($width, $height);
		$srcPath = $this->getFullPath();
		$dstPath = $this->getThumbDefaultPath($width, $height);

		if ($this->getType()->toInteger() == PostType::Youtube)
		{
			return ThumbnailHelper::generateFromUrl(
				'http://img.youtube.com/vi/' . $this->getFileHash() . '/mqdefault.jpg',
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
		$this->setFileSize(filesize($srcPath));
		$this->setFileHash(md5_file($srcPath));
		$this->setOriginalName($origName);

		if ($this->getFileSize() == 0)
			throw new SimpleException('Specified file is empty');

		$this->setMimeType(mime_content_type($srcPath));
		switch ($this->getMimeType())
		{
			case 'image/gif':
			case 'image/png':
			case 'image/jpeg':
				list ($imageWidth, $imageHeight) = getimagesize($srcPath);
				$this->setType(new PostType(PostType::Image));
				$this->setImageWidth($imageWidth);
				$this->setImageHeight($imageHeight);
				break;
			case 'application/x-shockwave-flash':
				list ($imageWidth, $imageHeight) = getimagesize($srcPath);
				$this->setType(new PostType(PostType::Flash));
				$this->setImageWidth($imageWidth);
				$this->setImageHeight($imageHeight);
				break;
			case 'video/webm':
			case 'video/mp4':
			case 'video/ogg':
			case 'application/ogg':
			case 'video/x-flv':
			case 'video/3gpp':
				list ($imageWidth, $imageHeight) = getimagesize($srcPath);
				$this->setType(new PostType(PostType::Video));
				$this->setImageWidth($imageWidth);
				$this->setImageHeight($imageHeight);
				break;
			default:
				throw new SimpleException('Invalid file type "%s"', $this->getMimeType());
		}

		$duplicatedPost = PostModel::tryGetByHash($this->getFileHash());
		if ($duplicatedPost !== null and (!$this->getId() or $this->getId() != $duplicatedPost->getId()))
		{
			throw new SimpleException(
				'Duplicate upload: %s',
				TextHelper::reprPost($duplicatedPost));
		}

		$dstPath = $this->getFullPath();

		TransferHelper::copy($srcPath, $dstPath);

		$thumbPath = $this->getThumbDefaultPath();
		if (file_exists($thumbPath))
			unlink($thumbPath);
	}

	public function setContentFromUrl($srcUrl)
	{
		if (!preg_match('/^https?:\/\//', $srcUrl))
			throw new SimpleException('Invalid URL "%s"', $srcUrl);

		$this->setOriginalName($srcUrl);

		if (preg_match('/youtube.com\/watch.*?=([a-zA-Z0-9_-]+)/', $srcUrl, $matches))
		{
			$youtubeId = $matches[1];
			$this->setType(new PostType(PostType::Youtube));
			$this->setMimeType(null);
			$this->setFileSize(null);
			$this->setFileHash($youtubeId);
			$this->setImageWidth(null);
			$this->setImageHeight(null);

			$thumbPath = $this->getThumbDefaultPath();
			if (file_exists($thumbPath))
				unlink($thumbPath);

			$duplicatedPost = PostModel::tryGetByHash($youtubeId);
			if ($duplicatedPost !== null and (!$this->getId() or $this->getId() != $duplicatedPost->getId()))
			{
				throw new SimpleException(
					'Duplicate upload: %s',
					TextHelper::reprPost($duplicatedPost));
			}
			return;
		}

		$tmpPath = tempnam(sys_get_temp_dir(), 'upload') . '.dat';

		try
		{
			$maxBytes = TextHelper::stripBytesUnits(ini_get('upload_max_filesize'));

			TransferHelper::download($srcUrl, $tmpPath, $maxBytes);

			$this->setContentFromPath($tmpPath, basename($srcUrl));
		}
		finally
		{
			if (file_exists($tmpPath))
				unlink($tmpPath);
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
		$x []= $this->getSource();
		$x []= $this->getFileHash();

		natcasesort($x);

		$x = join(' ', $x);
		return md5($x);
	}
}
