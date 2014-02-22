<?php
class PostEntity extends AbstractEntity
{
	public $type;
	public $name;
	public $origName;
	public $fileHash;
	public $fileSize;
	public $mimeType;
	public $safety;
	public $hidden;
	public $uploadDate;
	public $imageWidth;
	public $imageHeight;
	public $uploaderId;
	public $source;
	public $commentCount;
	public $favCount;
	public $score;

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
		$this->uploaderId = $user->id;
		$this->setCache('uploader', $user);
	}



	public function getComments()
	{
		if ($this->hasCache('comments'))
			return $this->getCache('comments');
		$comments = CommentModel::findAllByPostId($this->id);
		$this->setCache('comments', $comments);
		return $comments;
	}




	public function getFavorites()
	{
		if ($this->hasCache('favoritee'))
			return $this->getCache('favoritee');
		$stmt = new SqlSelectStatement();
		$stmt->setColumn('user.*');
		$stmt->setTable('user');
		$stmt->addInnerJoin('favoritee', new SqlEqualsOperator('favoritee.user_id', 'user.id'));
		$stmt->setCriterion(new SqlEqualsOperator('favoritee.post_id', new SqlBinding($this->id)));
		$rows = Database::fetchAll($stmt);
		$favorites = UserModel::convertRows($rows);
		$this->setCache('favoritee', $favorites);
		return $favorites;
	}



	public function getRelations()
	{
		if ($this->hasCache('relations'))
			return $this->getCache('relations');

		$stmt = new SqlSelectStatement();
		$stmt->setColumn('post.*');
		$stmt->setTable('post');
		$binding = new SqlBinding($this->id);
		$stmt->addInnerJoin('crossref', (new SqlDisjunction)
			->add(
				(new SqlConjunction)
					->add(new SqlEqualsOperator('post.id', 'crossref.post2_id'))
					->add(new SqlEqualsOperator('crossref.post_id', $binding)))
			->add(
				(new SqlConjunction)
					->add(new SqlEqualsOperator('post.id', 'crossref.post_id'))
					->add(new SqlEqualsOperator('crossref.post2_id', $binding))));
		$rows = Database::fetchAll($stmt);
		$posts = PostModel::convertRows($rows);
		$this->setCache('relations', $posts);
		return $posts;
	}

	public function setRelations(array $relations)
	{
		foreach ($relations as $relatedPost)
			if (!$relatedPost->id)
				throw new Exception('All related posts must be saved');
		$uniqueRelations = [];
		foreach ($relations as $relatedPost)
			$uniqueRelations[$relatedPost->id] = $relatedPost;
		$relations = array_values($uniqueRelations);
		$this->setCache('relations', $relations);
	}

	public function setRelationsFromText($relationsText)
	{
		$config = \Chibi\Registry::getConfig();
		$relatedIds = array_filter(preg_split('/\D/', $relationsText));

		$relatedPosts = [];
		foreach ($relatedIds as $relatedId)
		{
			if ($relatedId == $this->id)
				continue;

			if (count($relatedPosts) > $config->browsing->maxRelatedPosts)
				throw new SimpleException('Too many related posts (maximum: ' . $config->browsing->maxRelatedPosts . ')');

			$relatedPosts []= PostModel::findById($relatedId);
		}

		$this->setRelations($relatedPosts);
	}



	public function getTags()
	{
		if ($this->hasCache('tags'))
			return $this->getCache('tags');
		$tags = TagModel::findAllByPostId($this->id);
		$this->setCache('tags', $tags);
		return $tags;
	}

	public function setTags(array $tags)
	{
		foreach ($tags as $tag)
			if (!$tag->id)
				throw new Exception('All tags must be saved');
		$uniqueTags = [];
		foreach ($tags as $tag)
			$uniqueTags[$tag->id] = $tag;
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
				$tag->name = $tagName;
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
			if (trim(strtolower($tag->name)) == $tagName)
				return true;
		return false;
	}




	public function setHidden($hidden)
	{
		$this->hidden = boolval($hidden);
	}

	public function setSafety($safety)
	{
		$this->safety = PostModel::validateSafety($safety);
	}

	public function setSource($source)
	{
		$this->source = PostModel::validateSource($source);
	}


	public function getThumbCustomPath($width = null, $height = null)
	{
		return PostModel::getThumbCustomPath($this->name, $width, $height);
	}

	public function getThumbDefaultPath($width = null, $height = null)
	{
		return PostModel::getThumbDefaultPath($this->name, $width, $height);
	}

	public function getFullPath()
	{
		return PostModel::getFullPath($this->name);
	}

	public function hasCustomThumb($width = null, $height = null)
	{
		$thumbPath = $this->getThumbCustomPath($width, $height);
		return file_exists($thumbPath);
	}

	public function setCustomThumbnailFromPath($srcPath)
	{
		$config = \Chibi\Registry::getConfig();

		$mimeType = mime_content_type($srcPath);
		if (!in_array($mimeType, ['image/gif', 'image/png', 'image/jpeg']))
			throw new SimpleException('Invalid thumbnail type "' . $mimeType . '"');

		list ($imageWidth, $imageHeight) = getimagesize($srcPath);
		if ($imageWidth != $config->browsing->thumbWidth)
			throw new SimpleException('Invalid thumbnail width (should be ' . $config->browsing->thumbWidth . ')');
		if ($imageHeight != $config->browsing->thumbHeight)
			throw new SimpleException('Invalid thumbnail height (should be ' . $config->browsing->thumbHeight . ')');

		$dstPath = $this->getThumbCustomPath();

		if (is_uploaded_file($srcPath))
			move_uploaded_file($srcPath, $dstPath);
		else
			rename($srcPath, $dstPath);
	}

	public function makeThumb($width = null, $height = null)
	{
		list ($width, $height) = PostModel::validateThumbSize($width, $height);
		$dstPath = $this->getThumbDefaultPath($width, $height);
		$srcPath = $this->getFullPath();

		if ($this->type == PostType::Youtube)
		{
			$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';
			$contents = file_get_contents('http://img.youtube.com/vi/' . $this->fileHash . '/mqdefault.jpg');
			file_put_contents($tmpPath, $contents);
			if (file_exists($tmpPath))
				$srcImage = imagecreatefromjpeg($tmpPath);
		}
		else switch ($this->mimeType)
		{
			case 'image/jpeg':
				$srcImage = imagecreatefromjpeg($srcPath);
				break;
			case 'image/png':
				$srcImage = imagecreatefrompng($srcPath);
				break;
			case 'image/gif':
				$srcImage = imagecreatefromgif($srcPath);
				break;
			case 'application/x-shockwave-flash':
				$srcImage = null;
				exec('which dump-gnash', $tmp, $exitCode);
				if ($exitCode == 0)
				{
					$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.png';
					exec('dump-gnash --screenshot last --screenshot-file ' . $tmpPath . ' -1 -r1 --max-advances 15 ' . $srcPath);
					if (file_exists($tmpPath))
						$srcImage = imagecreatefrompng($tmpPath);
				}
				if (!$srcImage)
				{
					exec('which swfrender', $tmp, $exitCode);
					if ($exitCode == 0)
					{
						$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.png';
						exec('swfrender ' . $srcPath . ' -o ' . $tmpPath);
						if (file_exists($tmpPath))
							$srcImage = imagecreatefrompng($tmpPath);
					}
				}
				break;
			default:
				break;
		}

		if (isset($tmpPath))
			unlink($tmpPath);

		if (!isset($srcImage))
			return false;

		$config = \Chibi\Registry::getConfig();
		switch ($config->browsing->thumbStyle)
		{
			case 'outside':
				$dstImage = ThumbnailHelper::cropOutside($srcImage, $width, $height);
				break;
			case 'inside':
				$dstImage = ThumbnailHelper::cropInside($srcImage, $width, $height);
				break;
			default:
				throw new SimpleException('Unknown thumbnail crop style');
		}

		imagejpeg($dstImage, $dstPath);
		imagedestroy($srcImage);
		imagedestroy($dstImage);

		return true;
	}



	public function setContentFromPath($srcPath)
	{
		$this->fileSize = filesize($srcPath);
		$this->fileHash = md5_file($srcPath);

		if ($this->fileSize == 0)
			throw new SimpleException('Specified file is empty');

		$this->mimeType = mime_content_type($srcPath);
		switch ($this->mimeType)
		{
			case 'image/gif':
			case 'image/png':
			case 'image/jpeg':
				list ($imageWidth, $imageHeight) = getimagesize($srcPath);
				$this->type = PostType::Image;
				$this->imageWidth = $imageWidth;
				$this->imageHeight = $imageHeight;
				break;
			case 'application/x-shockwave-flash':
				list ($imageWidth, $imageHeight) = getimagesize($srcPath);
				$this->type = PostType::Flash;
				$this->imageWidth = $imageWidth;
				$this->imageHeight = $imageHeight;
				break;
			default:
				throw new SimpleException('Invalid file type "' . $this->mimeType . '"');
		}

		$duplicatedPost = PostModel::findByHash($this->fileHash, false);
		if ($duplicatedPost !== null and (!$this->id or $this->id != $duplicatedPost->id))
			throw new SimpleException('Duplicate upload: @' . $duplicatedPost->id);

		$dstPath = $this->getFullPath();

		if (is_uploaded_file($srcPath))
			move_uploaded_file($srcPath, $dstPath);
		else
			rename($srcPath, $dstPath);

		$thumbPath = $this->getThumbDefaultPath();
		if (file_exists($thumbPath))
			unlink($thumbPath);
	}

	public function setContentFromUrl($srcUrl)
	{
		if (!preg_match('/^https?:\/\//', $srcUrl))
			throw new SimpleException('Invalid URL "' . $srcUrl . '"');

		if (preg_match('/youtube.com\/watch.*?=([a-zA-Z0-9_-]+)/', $srcUrl, $matches))
		{
			$youtubeId = $matches[1];
			$this->type = PostType::Youtube;
			$this->mimeType = null;
			$this->fileSize = null;
			$this->fileHash = $youtubeId;
			$this->imageWidth = null;
			$this->imageHeight = null;

			$thumbPath = $this->getThumbDefaultPath();
			if (file_exists($thumbPath))
				unlink($thumbPath);

			$duplicatedPost = PostModel::findByHash($youtubeId, false);
			if ($duplicatedPost !== null and (!$this->id or $this->id != $duplicatedPost->id))
				throw new SimpleException('Duplicate upload: @' . $duplicatedPost->id);
			return;
		}

		$srcPath = tempnam(sys_get_temp_dir(), 'upload') . '.dat';

		//warning: low level sh*t ahead
		//download the URL $srcUrl into $srcPath
		$maxBytes = TextHelper::stripBytesUnits(ini_get('upload_max_filesize'));
		set_time_limit(0);
		$urlFP = fopen($srcUrl, 'rb');
		if (!$urlFP)
			throw new SimpleException('Cannot open URL for reading');
		$srcFP = fopen($srcPath, 'w+b');
		if (!$srcFP)
		{
			fclose($urlFP);
			throw new SimpleException('Cannot open file for writing');
		}

		try
		{
			while (!feof($urlFP))
			{
				$buffer = fread($urlFP, 4 * 1024);
				if (fwrite($srcFP, $buffer) === false)
					throw new SimpleException('Cannot write into file');
				fflush($srcFP);
				if (ftell($srcFP) > $maxBytes)
					throw new SimpleException('File is too big (maximum allowed size: ' . TextHelper::useBytesUnits($maxBytes) . ')');
			}
		}
		finally
		{
			fclose($urlFP);
			fclose($srcFP);
		}

		try
		{
			$this->setContentFromPath($srcPath);
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
			$x []= TextHelper::reprTag($tag->name);
		foreach ($this->getRelations() as $relatedPost)
			$x []= TextHelper::reprPost($relatedPost);
		$x []= $this->safety;
		$x []= $this->source;
		$x []= $this->fileHash;
		natcasesort($x);
		$x = join(' ', $x);
		return md5($x);
	}
}
