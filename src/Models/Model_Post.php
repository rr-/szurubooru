<?php
class Model_Post extends AbstractModel
{
	protected static $config;

	public static function initModel()
	{
		self::$config = \Chibi\Registry::getConfig();
	}

	public static function getTableName()
	{
		return 'post';
	}

	public static function getQueryBuilder()
	{
		return 'Model_Post_QueryBuilder';
	}



	public static function locate($key, $disallowNumeric = false, $throw = true)
	{
		if (is_numeric($key) and !$disallowNumeric)
		{
			$post = R::findOne(self::getTableName(), 'id = ?', [$key]);
			if (!$post)
			{
				if ($throw)
					throw new SimpleException('Invalid post ID "' . $key . '"');
				return null;
			}
		}
		else
		{
			$post = R::findOne(self::getTableName(), 'name = ?', [$key]);
			if (!$post)
			{
				if ($throw)
					throw new SimpleException('Invalid post name "' . $key . '"');
				return null;
			}
		}
		return $post;
	}

	public static function create()
	{
		$post = R::dispense(self::getTableName());
		$post->hidden = false;
		$post->upload_date = time();
		do
		{
			$post->name = md5(mt_rand() . uniqid());
		}
		while (file_exists(self::getFullPath($post->name)));
		return $post;
	}

	public static function remove($post)
	{
		//remove stuff from auxiliary tables
		R::trashAll(R::find('postscore', 'post_id = ?', [$post->id]));
		R::trashAll(R::find('crossref', 'post_id = ? OR post2_id = ?', [$post->id, $post->id]));
		foreach ($post->ownComment as $comment)
		{
			$comment->post = null;
			R::store($comment);
		}
		$post->ownFavoritee = [];
		$post->sharedTag = [];
		R::store($post);
		R::trash($post);
	}

	public static function save($post)
	{
		R::store($post);
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

	private static function validateThumbSize($width, $height)
	{
		$width = $width === null ? self::$config->browsing->thumbWidth : $width;
		$height = $height === null ? self::$config->browsing->thumbHeight : $height;
		$width = min(1000, max(1, $width));
		$height = min(1000, max(1, $height));
		return [$width, $height];
	}

	public static function getAllPostCount()
	{
		return R::$f
			->begin()
			->select('count(1)')
			->as('count')
			->from(self::getTableName())
			->get('row')['count'];
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

	public static function attachTags($posts)
	{
		//slow!!!
		//R::preload($posts, 'sharedTag|tag');
		$ids = array_map(function($x) { return $x->id; }, $posts);
		$sql = 'SELECT post_tag.post_id, tag.* FROM tag INNER JOIN post_tag ON post_tag.tag_id = tag.id WHERE post_id IN (' . R::genSlots($ids) . ')';
		$rows = R::getAll($sql, $ids);
		$postMap = array_fill_keys($ids, []);
		foreach ($rows as $row)
		{
			$postMap[$row['post_id']] []= $row;
		}
		foreach ($posts as $post)
		{
			$tagRows = $postMap[$post->id];
			$tags = self::convertRows($tagRows, 'tag', true);
			$post->setProperty('sharedTag', $tags, true, true);
		}
	}

	public function isTaggedWith($tagName)
	{
		$tagName = trim(strtolower($tagName));
		foreach ($this->sharedTag as $tag)
			if (trim(strtolower($tag->name)) == $tagName)
				return true;
		return false;
	}

	public function hasCustomThumb($width = null, $height = null)
	{
		$thumbPath = self::getThumbCustomPath($this->name, $width, $height);
		return file_exists($thumbPath);
	}



	public function setHidden($hidden)
	{
		$this->hidden = boolval($hidden);
	}



	public function setSafety($safety)
	{
		$this->safety = self::validateSafety($safety);
	}



	public function setSource($source)
	{
		$this->source = self::validateSource($source);
	}



	public function setTagsFromText($tagsText)
	{
		$tagNames = Model_Tag::validateTags($tagsText);
		$dbTags = Model_Tag::insertOrUpdate($tagNames);

		$this->sharedTag = $dbTags;
	}



	public function setRelationsFromText($relationsText)
	{
		$relatedIds = array_filter(preg_split('/\D/', $relationsText));

		$relatedPosts = [];
		foreach ($relatedIds as $relatedId)
		{
			if ($relatedId == $this->id)
				continue;

			if (count($relatedPosts) > self::$config->browsing->maxRelatedPosts)
				throw new SimpleException('Too many related posts (maximum: ' . self::$config->browsing->maxRelatedPosts . ')');

			$relatedPosts []= self::locate($relatedId);
		}

		$this->bean->via('crossref')->sharedPost = $relatedPosts;
	}



	public function setCustomThumbnailFromPath($srcPath)
	{
		$mimeType = mime_content_type($srcPath);
		if (!in_array($mimeType, ['image/gif', 'image/png', 'image/jpeg']))
			throw new SimpleException('Invalid thumbnail type "' . $mimeType . '"');

		list ($imageWidth, $imageHeight) = getimagesize($srcPath);
		if ($imageWidth != self::$config->browsing->thumbWidth)
			throw new SimpleException('Invalid thumbnail width (should be ' . self::$config->browsing->thumbWidth . ')');
		if ($imageWidth != self::$config->browsing->thumbHeight)
			throw new SimpleException('Invalid thumbnail width (should be ' . self::$config->browsing->thumbHeight . ')');

		$dstPath = self::getThumbCustomPath($this->name);

		if (is_uploaded_file($srcPath))
			move_uploaded_file($srcPath, $dstPath);
		else
			rename($srcPath, $dstPath);
	}



	public function setContentFromPath($srcPath)
	{
		$this->file_size = filesize($srcPath);
		$this->file_hash = md5_file($srcPath);

		if ($this->file_size == 0)
			throw new SimpleException('Specified file is empty');

		$this->mime_type = mime_content_type($srcPath);
		switch ($this->mime_type)
		{
			case 'image/gif':
			case 'image/png':
			case 'image/jpeg':
				list ($imageWidth, $imageHeight) = getimagesize($srcPath);
				$this->type = PostType::Image;
				$this->image_width = $imageWidth;
				$this->image_height = $imageHeight;
				break;
			case 'application/x-shockwave-flash':
				list ($imageWidth, $imageHeight) = getimagesize($srcPath);
				$this->type = PostType::Flash;
				$this->image_width = $imageWidth;
				$this->image_height = $imageHeight;
				break;
			default:
				throw new SimpleException('Invalid file type "' . $this->mime_type . '"');
		}

		$this->orig_name = basename($srcPath);
		$duplicatedPost = R::findOne('post', 'file_hash = ?', [$this->file_hash]);
		if ($duplicatedPost !== null and (!$this->id or $this->id != $duplicatedPost->id))
			throw new SimpleException('Duplicate upload: @' . $duplicatedPost->id);

		$dstPath = $this->getFullPath($this->name);

		if (is_uploaded_file($srcPath))
			move_uploaded_file($srcPath, $dstPath);
		else
			rename($srcPath, $dstPath);

		$thumbPath = self::getThumbDefaultPath($this->name);
		if (file_exists($thumbPath))
			unlink($thumbPath);
	}



	public function setContentFromUrl($srcUrl)
	{
		$this->orig_name = $srcUrl;

		if (!preg_match('/^https?:\/\//', $srcUrl))
			throw new SimpleException('Invalid URL "' . $srcUrl . '"');

		if (preg_match('/youtube.com\/watch.*?=([a-zA-Z0-9_-]+)/', $srcUrl, $matches))
		{
			$origName = $matches[1];
			$this->orig_name = $origName;
			$this->type = PostType::Youtube;
			$this->mime_type = null;
			$this->file_size = null;
			$this->file_hash = null;
			$this->image_width = null;
			$this->image_height = null;

			$thumbPath = self::getThumbDefaultPath($this->name);
			if (file_exists($thumbPath))
				unlink($thumbPath);

			$duplicatedPost = R::findOne('post', 'orig_name = ?', [$origName]);
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



	public function makeThumb($width = null, $height = null)
	{
		list ($width, $height) = self::validateThumbSize($width, $height);
		$dstPath = self::getThumbDefaultPath($this->name, $width, $height);
		$srcPath = self::getFullPath($this->name);

		if ($this->type == PostType::Youtube)
		{
			$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';
			$contents = file_get_contents('http://img.youtube.com/vi/' . $this->orig_name . '/mqdefault.jpg');
			file_put_contents($tmpPath, $contents);
			if (file_exists($tmpPath))
				$srcImage = imagecreatefromjpeg($tmpPath);
		}
		else switch ($this->mime_type)
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

		switch (self::$config->browsing->thumbStyle)
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
}

Model_Post::initModel();
