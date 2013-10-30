<?php
class PostController
{
	public function workWrapper($callback)
	{
		$this->context->stylesheets []= '../lib/tagit/jquery.tagit.css';
		$this->context->scripts []= '../lib/tagit/jquery.tagit.js';
		$callback();
	}

	private static function serializeTags($post)
	{
		$x = [];
		foreach ($post->sharedTag as $tag)
			$x []= $tag->name;
		natcasesort($x);
		$x = join('', $x);
		return md5($x);
	}

	private static function handleUploadErrors($file)
	{
		switch ($file['error'])
		{
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_INI_SIZE:
				throw new SimpleException('File is too big (maximum size allowed: ' . ini_get('upload_max_filesize') . ')');
			case UPLOAD_ERR_FORM_SIZE:
				throw new SimpleException('File is too big than it was allowed in HTML form');
			case UPLOAD_ERR_PARTIAL:
				throw new SimpleException('File transfer was interrupted');
			case UPLOAD_ERR_NO_FILE:
				throw new SimpleException('No file was uploaded');
			case UPLOAD_ERR_NO_TMP_DIR:
				throw new SimpleException('Server misconfiguration error: missing temporary folder');
			case UPLOAD_ERR_CANT_WRITE:
				throw new SimpleException('Server misconfiguration error: cannot write to disk');
			case UPLOAD_ERR_EXTENSION:
				throw new SimpleException('Server misconfiguration error: upload was canceled by an extension');
			default:
				throw new SimpleException('Generic file upload error (id: ' . $file['error'] . ')');
		}
		if (!is_uploaded_file($file['tmp_name']))
			throw new SimpleException('Generic file upload error');
	}



	/**
	* @route /{source}
	* @route /{source}/{page}
	* @route /{source}/{query}/
	* @route /{source}/{query}/{page}
	* @route /{source}/{additionalInfo}/{query}/
	* @route /{source}/{additionalInfo}/{query}/{page}
	* @validate source posts|mass-tag
	* @validate page \d*
	* @validate query [^\/]*
	* @validate additionalInfo [^\/]*
	*/
	public function listAction($query = null, $page = 1, $source = 'posts', $additionalInfo = null)
	{
		$this->context->stylesheets []= 'post-small.css';
		$this->context->stylesheets []= 'post-list.css';
		$this->context->stylesheets []= 'paginator.css';
		if ($this->context->user->hasEnabledEndlessScrolling())
			$this->context->scripts []= 'paginator-endless.js';
		if ($source == 'mass-tag')
			$this->context->scripts []= 'mass-tag.js';
		$this->context->source = $source;
		$this->context->additionalInfo = $additionalInfo;

		//redirect requests in form of /posts/?query=... to canonical address
		$formQuery = InputHelper::get('query');
		if ($formQuery !== null)
		{
			$this->context->transport->searchQuery = $formQuery;
			if (strpos($formQuery, '/') !== false)
				throw new SimpleException('Search query contains invalid characters');
			$url = \Chibi\UrlHelper::route('post', 'list', ['source' => $source, 'additionalInfo' => $additionalInfo, 'query' => urlencode($formQuery)]);
			\Chibi\UrlHelper::forward($url);
			return;
		}

		$query = trim(urldecode($query));
		$page = intval($page);
		$postsPerPage = intval($this->config->browsing->postsPerPage);
		$this->context->subTitle = 'posts';
		$this->context->transport->searchQuery = $query;
		PrivilegesHelper::confirmWithException(Privilege::ListPosts);
		if ($source == 'mass-tag')
		{
			PrivilegesHelper::confirmWithException(Privilege::MassTag);
			$this->context->massTagTag = $additionalInfo;
			$this->context->massTagQuery = $query;
		}

		$postCount = Model_Post::getEntityCount($query);
		$pageCount = ceil($postCount / $postsPerPage);
		$page = max(1, min($pageCount, $page));
		$posts = Model_Post::getEntities($query, $postsPerPage, $page);

		$this->context->transport->paginator = new StdClass;
		$this->context->transport->paginator->page = $page;
		$this->context->transport->paginator->pageCount = $pageCount;
		$this->context->transport->paginator->entityCount = $postCount;
		$this->context->transport->paginator->entities = $posts;
		$this->context->transport->posts = $posts;
	}



	/**
	* @route /post/{id}/toggle-tag/{tag}
	* @validate tag [^\/]*
	*/
	public function toggleTagAction($id, $tag)
	{
		$post = Model_Post::locate($id);
		R::preload($post, ['uploader' => 'user']);
		$this->context->transport->post = $post;
		$tag = Model_Tag::validateTag($tag);

		if (InputHelper::get('submit'))
		{
			PrivilegesHelper::confirmWithException(Privilege::MassTag);
			$tags = array_map(function($x) { return $x->name; }, $post->sharedTag);

			if (in_array($tag, $tags))
				$tags = array_diff($tags, [$tag]);
			else
				$tags += [$tag];

			$dbTags = Model_Tag::insertOrUpdate($tags);
			$post->sharedTag = $dbTags;

			R::store($post);
			$this->context->transport->success = true;
		}
	}



	/**
	* @route /favorites
	* @route /favorites/{page}
	* @validate page \d*
	*/
	public function favoritesAction($page = 1)
	{
		$this->listAction('favmin:1', $page);
		$this->context->viewName = 'post-list';
	}



	/**
	* @route /random
	* @route /random/{page}
	* @validate page \d*
	*/
	public function randomAction($page = 1)
	{
		$this->listAction('order:random', $page);
		$this->context->viewName = 'post-list';
	}



	/**
	* @route /post/upload
	*/
	public function uploadAction()
	{
		$this->context->stylesheets []= 'upload.css';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->scripts []= 'upload.js';
		$this->context->subTitle = 'upload';
		PrivilegesHelper::confirmWithException(Privilege::UploadPost);
		if ($this->config->registration->needEmailForUploading)
			PrivilegesHelper::confirmEmail($this->context->user);

		if (InputHelper::get('submit'))
		{
			/* file contents */
			if (isset($_FILES['file']))
			{
				$suppliedFile = $_FILES['file'];
				self::handleUploadErrors($suppliedFile);
				$origName = basename($suppliedFile['name']);
				$sourcePath = $suppliedFile['tmp_name'];
			}
			elseif (InputHelper::get('url'))
			{
				$url = InputHelper::get('url');
				$origName = $url;
				if (!preg_match('/^https?:\/\//', $url))
					throw new SimpleException('Invalid URL "' . $url . '"');

				if (preg_match('/youtube.com\/watch.*?=([a-zA-Z0-9_-]+)/', $url, $matches))
				{
					$origName = $matches[1];
					$postType = PostType::Youtube;
					$sourcePath = null;
				}
				else
				{
					$sourcePath = tempnam(sys_get_temp_dir(), 'upload') . '.dat';

					//warning: low level sh*t ahead
					//download the URL $url into $sourcePath
					$maxBytes = TextHelper::stripBytesUnits(ini_get('upload_max_filesize'));
					set_time_limit(0);
					$urlFP = fopen($url, 'rb');
					if (!$urlFP)
						throw new SimpleException('Cannot open URL for reading');
					$sourceFP = fopen($sourcePath, 'w+b');
					if (!$sourceFP)
					{
						fclose($urlFP);
						throw new SimpleException('Cannot open file for writing');
					}
					try
					{
						while (!feof($urlFP))
						{
							$buffer = fread($urlFP, 4 * 1024);
							if (fwrite($sourceFP, $buffer) === false)
								throw new SimpleException('Cannot write into file');
							fflush($sourceFP);
							if (ftell($sourceFP) > $maxBytes)
								throw new SimpleException('File is too big (maximum allowed size: ' . TextHelper::useBytesUnits($maxBytes) . ')');
						}
					}
					finally
					{
						fclose($urlFP);
						fclose($sourceFP);
					}
				}
			}


			/* file details */
			$mimeType = $sourcePath ? mime_content_type($sourcePath) : null;
			$imageWidth = null;
			$imageHeight = null;
			switch ($mimeType)
			{
				case 'image/gif':
				case 'image/png':
				case 'image/jpeg':
					$postType = PostType::Image;
					list ($imageWidth, $imageHeight) = getimagesize($sourcePath);
					break;
				case 'application/x-shockwave-flash':
					$postType = PostType::Flash;
					list ($imageWidth, $imageHeight) = getimagesize($sourcePath);
					break;
				default:
					if (!isset($postType))
						throw new SimpleException('Invalid file type "' . $mimeType . '"');
			}

			if ($sourcePath)
			{
				$fileSize = filesize($sourcePath);
				$fileHash = md5_file($sourcePath);
				$duplicatedPost = R::findOne('post', 'file_hash = ?', [$fileHash]);
				if ($duplicatedPost !== null)
					throw new SimpleException('Duplicate upload: @' . $duplicatedPost->id);
			}
			else
			{
				$fileSize = 0;
				$fileHash = null;
				if ($postType == PostType::Youtube)
				{
					$duplicatedPost = R::findOne('post', 'orig_name = ?', [$origName]);
					if ($duplicatedPost !== null)
						throw new SimpleException('Duplicate upload: @' . $duplicatedPost->id);
				}
			}

			do
			{
				$name = md5(mt_rand() . uniqid());
				$path = $this->config->main->filesPath . DS . $name;
			}
			while (file_exists($path));


			/* safety */
			$suppliedSafety = InputHelper::get('safety');
			$suppliedSafety = Model_Post::validateSafety($suppliedSafety);

			/* tags */
			$suppliedTags = InputHelper::get('tags');
			$suppliedTags = Model_Tag::validateTags($suppliedTags);
			$dbTags = Model_Tag::insertOrUpdate($suppliedTags);

			/* source */
			$suppliedSource = InputHelper::get('source');
			$suppliedSource = Model_Post::validateSource($suppliedSource);

			/* db storage */
			$dbPost = R::dispense('post');
			$dbPost->type = $postType;
			$dbPost->name = $name;
			$dbPost->orig_name = $origName;
			$dbPost->file_hash = $fileHash;
			$dbPost->file_size = $fileSize;
			$dbPost->mime_type = $mimeType;
			$dbPost->safety = $suppliedSafety;
			$dbPost->source = $suppliedSource;
			$dbPost->hidden = false;
			$dbPost->upload_date = time();
			$dbPost->image_width = $imageWidth;
			$dbPost->image_height = $imageHeight;
			if ($this->context->loggedIn and !InputHelper::get('anonymous'))
				$dbPost->uploader = $this->context->user;
			$dbPost->ownFavoritee = [];
			$dbPost->sharedTag = $dbTags;

			if ($sourcePath)
			{
				if (is_uploaded_file($sourcePath))
					move_uploaded_file($sourcePath, $path);
				else
					rename($sourcePath, $path);
			}
			R::store($dbPost);

			$this->context->transport->success = true;
		}
	}



	/**
	* @route /post/{id}/edit
	*/
	public function editAction($id)
	{
		$post = Model_Post::locate($id);
		R::preload($post, ['uploader' => 'user']);
		$this->context->transport->post = $post;

		if (InputHelper::get('submit'))
		{
			/* safety */
			$suppliedSafety = InputHelper::get('safety');
			if ($suppliedSafety !== null)
			{
				PrivilegesHelper::confirmWithException(Privilege::EditPostSafety, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));
				$suppliedSafety = Model_Post::validateSafety($suppliedSafety);
				$post->safety = $suppliedSafety;
				$edited = true;
			}


			/* tags */
			$suppliedTags = InputHelper::get('tags');
			if ($suppliedTags !== null)
			{
				PrivilegesHelper::confirmWithException(Privilege::EditPostTags, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));
				$currentToken = self::serializeTags($post);
				if (InputHelper::get('tags-token') != $currentToken)
					throw new SimpleException('Someone else has changed the tags in the meantime');

				$suppliedTags = Model_Tag::validateTags($suppliedTags);
				$dbTags = Model_Tag::insertOrUpdate($suppliedTags);
				$post->sharedTag = $dbTags;
				$edited = true;
			}


			/* thumbnail */
			if (!empty($_FILES['thumb']['name']))
			{
				PrivilegesHelper::confirmWithException(Privilege::EditPostThumb, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));
				$suppliedFile = $_FILES['thumb'];
				self::handleUploadErrors($suppliedFile);

				$mimeType = mime_content_type($suppliedFile['tmp_name']);
				if (!in_array($mimeType, ['image/gif', 'image/png', 'image/jpeg']))
					throw new SimpleException('Invalid thumbnail type "' . $mimeType . '"');
				list ($imageWidth, $imageHeight) = getimagesize($suppliedFile['tmp_name']);
				if ($imageWidth != $this->config->browsing->thumbWidth)
					throw new SimpleException('Invalid thumbnail width (should be ' . $this->config->browsing->thumbWidth . ')');
				if ($imageWidth != $this->config->browsing->thumbHeight)
					throw new SimpleException('Invalid thumbnail width (should be ' . $this->config->browsing->thumbHeight . ')');

				$path = $this->config->main->thumbsPath . DS . $post->name . '.custom';
				move_uploaded_file($suppliedFile['tmp_name'], $path);
			}


			/* source */
			$suppliedSource = InputHelper::get('source');
			if ($suppliedSource !== null)
			{
				PrivilegesHelper::confirmWithException(Privilege::EditPostSource, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));
				$suppliedSource = Model_Post::validateSource($suppliedSource);
				$post->source = $suppliedSource;
				$edited = true;
			}

			R::store($post);
			$this->context->transport->success = true;
		}
	}



	/**
	* @route /post/{id}/hide
	*/
	public function hideAction($id)
	{
		$post = Model_Post::locate($id);
		R::preload($post, ['uploader' => 'user']);
		PrivilegesHelper::confirmWithException(Privilege::HidePost, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));
		if (InputHelper::get('submit'))
		{
			$post->hidden = true;
			R::store($post);
			$this->context->transport->success = true;
		}
	}

	/**
	* @route /post/{id}/unhide
	*/
	public function unhideAction($id)
	{
		$post = Model_Post::locate($id);
		R::preload($post, ['uploader' => 'user']);
		PrivilegesHelper::confirmWithException(Privilege::HidePost, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));
		if (InputHelper::get('submit'))
		{
			$post->hidden = false;
			R::store($post);
			$this->context->transport->success = true;
		}
	}

	/**
	* @route /post/{id}/delete
	*/
	public function deleteAction($id)
	{
		$post = Model_Post::locate($id);
		R::preload($post, ['uploader' => 'user']);
		PrivilegesHelper::confirmWithException(Privilege::DeletePost, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));
		if (InputHelper::get('submit'))
		{
			//remove stuff from auxiliary tables
			$post->ownFavoritee = [];
			$post->sharedTag = [];
			R::store($post);
			R::trash($post);
			$this->context->transport->success = true;
		}
	}



	/**
	* @route /post/{id}/add-fav
	* @route /post/{id}/fav-add
	*/
	public function addFavoriteAction($id)
	{
		$post = Model_Post::locate($id);
		R::preload($post, ['favoritee' => 'user']);
		PrivilegesHelper::confirmWithException(Privilege::FavoritePost);

		if (InputHelper::get('submit'))
		{
			if (!$this->context->loggedIn)
				throw new SimpleException('Not logged in');

			foreach ($post->via('favoritee')->sharedUser as $fav)
				if ($fav->id == $this->context->user->id)
					throw new SimpleException('Already in favorites');

			$post->link('favoritee')->user = $this->context->user;
			R::store($post);
			$this->context->transport->success = true;
		}
	}

	/**
	* @route /post/{id}/rem-fav
	* @route /post/{id}/fav-rem
	*/
	public function remFavoriteAction($id)
	{
		$post = Model_Post::locate($id);
		R::preload($post, ['favoritee' => 'user']);
		PrivilegesHelper::confirmWithException(Privilege::FavoritePost);

		if (InputHelper::get('submit'))
		{
			if (!$this->context->loggedIn)
				throw new SimpleException('Not logged in');

			$finalKey = null;
			foreach ($post->ownFavoritee as $key => $fav)
				if ($fav->user->id == $this->context->user->id)
					$finalKey = $key;

			if ($finalKey === null)
				throw new SimpleException('Not in favorites');

			unset ($post->ownFavoritee[$finalKey]);
			R::store($post);
			$this->context->transport->success = true;
		}
	}



	/**
	* @route /post/{id}/feature
	*/
	public function featureAction($id)
	{
		$post = Model_Post::locate($id);
		PrivilegesHelper::confirmWithException(Privilege::FeaturePost);
		Model_Property::set(Model_Property::FeaturedPostId, $post->id);
		Model_Property::set(Model_Property::FeaturedPostUserId, $this->context->user->id);
		Model_Property::set(Model_Property::FeaturedPostDate, time());
		$this->context->transport->success = true;
	}



	/**
	* Action that decorates the page containing the post.
	* @route /post/{id}
	*/
	public function viewAction($id)
	{
		$post = Model_Post::locate($id);
		R::preload($post, [
			'favoritee' => 'user',
			'uploader' => 'user',
			'tag',
			'comment',
			'ownComment.commenter' => 'user']);

		if ($post->hidden)
			PrivilegesHelper::confirmWithException(Privilege::ViewPost, 'hidden');
		PrivilegesHelper::confirmWithException(Privilege::ViewPost);
		PrivilegesHelper::confirmWithException(Privilege::ViewPost, PostSafety::toString($post->safety));

		$buildNextPostQuery = function($dbQuery, $id, $next)
		{
			$dbQuery->select('id')
				->from('post')
				->where($next ? 'id > ?' : 'id < ?')
				->put($id);
			$allowedSafety = array_filter(PostSafety::getAll(), function($safety)
			{
				return PrivilegesHelper::confirm(Privilege::ListPosts, PostSafety::toString($safety)) and
					$this->context->user->hasEnabledSafety($safety);
			});
			$dbQuery->and('safety')->in('(' . R::genSlots($allowedSafety) . ')');
			foreach ($allowedSafety as $s)
				$dbQuery->put($s);
			if (!PrivilegesHelper::confirm(Privilege::ListPosts, 'hidden'))
				$dbQuery->andNot('hidden');
			$dbQuery->orderBy($next ? 'id asc' : 'id desc')
				->limit(1);
		};

		$prevPostQuery = R::$f->begin();
		$buildNextPostQuery($prevPostQuery, $id, false);
		$prevPost = $prevPostQuery->get('row');

		$nextPostQuery = R::$f->begin();
		$buildNextPostQuery($nextPostQuery, $id, true);
		$nextPost = $nextPostQuery->get('row');

		$favorite = false;
		if ($this->context->loggedIn)
			foreach ($post->ownFavoritee as $fav)
				if ($fav->user->id == $this->context->user->id)
					$favorite = true;

		$this->context->stylesheets []= 'post-view.css';
		$this->context->stylesheets []= 'comment-small.css';
		$this->context->scripts []= 'post-view.js';
		$this->context->subTitle = 'showing @' . $post->id . ' &ndash; ' . join(', ', array_map(function($x) { return $x['name']; }, $post->sharedTag));
		$this->context->favorite = $favorite;
		$this->context->transport->post = $post;
		$this->context->transport->prevPostId = $prevPost ? $prevPost['id'] : null;
		$this->context->transport->nextPostId = $nextPost ? $nextPost['id'] : null;
		$this->context->transport->tagsToken = self::serializeTags($post);
	}



	/**
	* Action that renders the thumbnail of the requested file and sends it to user.
	* @route /post/{name}/thumb
	*/
	public function thumbAction($name)
	{
		$this->context->layoutName = 'layout-file';

		$path = $this->config->main->thumbsPath . DS . $name . '.custom';
		if (!file_exists($path))
			$path = $this->config->main->thumbsPath . DS . $name . '.default';
		if (!file_exists($path))
		{
			$post = Model_Post::locate($name);

			PrivilegesHelper::confirmWithException(Privilege::ViewPost);
			PrivilegesHelper::confirmWithException(Privilege::ViewPost, PostSafety::toString($post->safety));
			$srcPath = $this->config->main->filesPath . DS . $post->name;
			$dstWidth = $this->config->browsing->thumbWidth;
			$dstHeight = $this->config->browsing->thumbHeight;

			if ($post->type == PostType::Youtube)
			{
				$tmpPath = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';
				$contents = file_get_contents('http://img.youtube.com/vi/' . $post->orig_name . '/mqdefault.jpg');
				file_put_contents($tmpPath, $contents);
				if (file_exists($tmpPath))
					$srcImage = imagecreatefromjpeg($tmpPath);
			}
			else switch ($post->mime_type)
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

			if (isset($srcImage))
			{
				switch ($this->config->browsing->thumbStyle)
				{
					case 'outside':
						$dstImage = ThumbnailHelper::cropOutside($srcImage, $dstWidth, $dstHeight);
						break;
					case 'inside':
						$dstImage = ThumbnailHelper::cropInside($srcImage, $dstWidth, $dstHeight);
						break;
					default:
						throw new SimpleException('Unknown thumbnail crop style');
				}

				imagejpeg($dstImage, $path);
				imagedestroy($srcImage);
				imagedestroy($dstImage);
			}
			else
			{
				$path = $this->config->main->mediaPath . DS . 'img' . DS . 'thumb.jpg';
			}

			if (isset($tmpPath))
				unlink($tmpPath);
		}
		if (!is_readable($path))
			throw new SimpleException('Thumbnail file is not readable');

		$this->context->transport->cacheDaysToLive = 30;
		$this->context->transport->mimeType = 'image/jpeg';
		$this->context->transport->fileHash = 'thumb' . md5($name . filemtime($path));
		$this->context->transport->filePath = $path;
	}



	/**
	* Action that renders the requested file itself and sends it to user.
	* @route /post/{name}/retrieve
	*/
	public function retrieveAction($name)
	{
		$this->context->layoutName = 'layout-file';
		$post = Model_Post::locate($name, true);
		R::preload($post, ['tag']);

		PrivilegesHelper::confirmWithException(Privilege::RetrievePost);
		PrivilegesHelper::confirmWithException(Privilege::RetrievePost, PostSafety::toString($post->safety));

		$path = $this->config->main->filesPath . DS . $post->name;
		if (!file_exists($path))
			throw new SimpleException('Post file does not exist');
		if (!is_readable($path))
			throw new SimpleException('Post file is not readable');

		$ext = substr($post->orig_name, strrpos($post->orig_name, '.') + 1);
		if (strpos($post->orig_name, '.') === false)
			$ext = '.dat';
		$fn = sprintf('%s_%s_%s.%s',
			$this->config->main->title,
			$post->id, join(',', array_map(function($tag) { return $tag->name; }, $post->sharedTag)),
			$ext);
		$fn = preg_replace('/[[:^print:]]/', '', $fn);

		$ttl = 60 * 60 * 24 * 14;

		$this->context->transport->cacheDaysToLive = 14;
		$this->context->transport->customFileName = $fn;
		$this->context->transport->mimeType = $post->mimeType;
		$this->context->transport->fileHash = 'post' . $post->file_hash;
		$this->context->transport->filePath = $path;
	}
}
