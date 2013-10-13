<?php
class PostController
{
	public function workWrapper($callback)
	{
		$this->context->stylesheets []= '../lib/tagit/jquery.tagit.css';
		$this->context->scripts []= '../lib/tagit/jquery.tagit.js';
		$callback();
	}

	private static function locatePost($key)
	{
		if (is_numeric($key))
		{
			$post = R::findOne('post', 'id = ?', [$key]);
			if (!$post)
				throw new SimpleException('Invalid post ID "' . $key . '"');
		}
		else
		{
			$post = R::findOne('post', 'name = ?', [$key]);
			if (!$post)
				throw new SimpleException('Invalid post name "' . $key . '"');
		}
		return $post;
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
	* @route /posts
	* @route /posts/{page}
	* @route /posts/{query}
	* @route /posts/{query}/{page}
	* @validate page \d*
	* @validate query [^\/]*
	*/
	public function listAction($query = null, $page = 1)
	{
		$this->context->stylesheets []= 'post-list.css';
		$this->context->stylesheets []= 'paginator.css';
		if ($this->config->browsing->endlessScrolling)
			$this->context->scripts []= 'paginator-endless.js';

		#redirect requests in form of /posts/?query=... to canonical address
		$formQuery = InputHelper::get('query');
		if (!empty($formQuery))
		{
			$url = \Chibi\UrlHelper::route('post', 'list', ['query' => $formQuery]);
			\Chibi\UrlHelper::forward($url);
			return;
		}

		$this->context->subTitle = 'browsing posts';
		$page = intval($page);
		$postsPerPage = intval($this->config->browsing->postsPerPage);
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ListPosts);

		$buildDbQuery = function($dbQuery)
		{
			$dbQuery->from('post');

			$allowedSafety = array_filter(PostSafety::getAll(), function($safety)
			{
				return PrivilegesHelper::confirm($this->context->user, Privilege::ListPosts, PostSafety::toString($safety));
			});
			//todo safety [user choice]

			$dbQuery->where('safety IN (' . R::genSlots($allowedSafety) . ')');
			foreach ($allowedSafety as $s)
				$dbQuery->put($s);

			if (!PrivilegesHelper::confirm($this->context->user, Privilege::ListPosts, 'hidden'))
				$dbQuery->andNot('hidden');

			//todo construct WHERE based on filters

			//todo construct ORDER based on filers
		};

		$countDbQuery = R::$f->begin();
		$countDbQuery->select('COUNT(1) AS count');
		$buildDbQuery($countDbQuery);
		$postCount = intval($countDbQuery->get('row')['count']);
		$pageCount = ceil($postCount / $postsPerPage);

		$searchDbQuery = R::$f->begin();
		$searchDbQuery->select('*');
		$buildDbQuery($searchDbQuery);
		$searchDbQuery->orderBy('id DESC');
		$searchDbQuery->limit('?')->put($postsPerPage);
		$searchDbQuery->offset('?')->put(($page - 1) * $postsPerPage);

		$posts = $searchDbQuery->get();
		$this->context->transport->searchQuery = $query;
		$this->context->transport->page = $page;
		$this->context->transport->postCount = $postCount;
		$this->context->transport->pageCount = $pageCount;
		$this->context->transport->posts = $posts;
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
	* @route /post/upload
	*/
	public function uploadAction()
	{
		$this->context->stylesheets []= 'upload.css';
		$this->context->scripts []= 'upload.js';
		$this->context->subTitle = 'upload';
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::UploadPost);

		if (isset($_FILES['file']))
		{
			/* safety */
			$suppliedSafety = intval(InputHelper::get('safety'));
			if (!in_array($suppliedSafety, PostSafety::getAll()))
				throw new SimpleException('Invalid safety type "' . $suppliedSafety . '"');


			/* tags */
			$suppliedTags = InputHelper::get('tags');
			$suppliedTags = preg_split('/[,;\s+]/', $suppliedTags);
			$suppliedTags = array_filter($suppliedTags);
			$suppliedTags = array_unique($suppliedTags);
			foreach ($suppliedTags as $tag)
				if (!preg_match('/^[a-zA-Z0-9_-]+$/i', $tag))
					throw new SimpleException('Invalid tag "' . $tag . '"');
			if (empty($suppliedTags))
				throw new SimpleException('No tags set');

			$dbTags = [];
			foreach ($suppliedTags as $tag)
			{
				$dbTag = R::findOne('tag', 'name = ?', [$tag]);
				if (!$dbTag)
				{
					$dbTag = R::dispense('tag');
					$dbTag->name = $tag;
					R::store($dbTag);
				}
				$dbTags []= $dbTag;
			}


			/* file contents */
			$suppliedFile = $_FILES['file'];
			self::handleUploadErrors($suppliedFile);


			/* file details */
			$mimeType = mime_content_type($suppliedFile['tmp_name']);
			$imageWidth = null;
			$imageHeight = null;
			switch ($mimeType)
			{
				case 'image/gif':
				case 'image/png':
				case 'image/jpeg':
					$postType = PostType::Image;
					list ($imageWidth, $imageHeight) = getimagesize($suppliedFile['tmp_name']);
					break;
				case 'application/x-shockwave-flash':
					$postType = PostType::Flash;
					list ($imageWidth, $imageHeight) = getimagesize($suppliedFile['tmp_name']);
					break;
				default:
					throw new SimpleException('Invalid file type "' . $mimeType . '"');
			}

			$fileHash = md5_file($suppliedFile['tmp_name']);
			$duplicatedPost = R::findOne('post', 'file_hash = ?', [$fileHash]);
			if ($duplicatedPost !== null)
				throw new SimpleException('Duplicate upload');

			do
			{
				$name = md5(mt_rand() . uniqid());
				$path = $this->config->main->filesPath . DS . $name;
			}
			while (file_exists($path));


			/* db storage */
			$dbPost = R::dispense('post');
			$dbPost->type = $postType;
			$dbPost->name = $name;
			$dbPost->orig_name = basename($suppliedFile['name']);
			$dbPost->file_hash = $fileHash;
			$dbPost->file_size = filesize($suppliedFile['tmp_name']);
			$dbPost->mime_type = $mimeType;
			$dbPost->safety = $suppliedSafety;
			$dbPost->hidden = false;
			$dbPost->upload_date = time();
			$dbPost->image_width = $imageWidth;
			$dbPost->image_height = $imageHeight;
			$dbPost->uploader = $this->context->user;
			$dbPost->ownFavoritee = [];
			$dbPost->sharedTag = $dbTags;

			move_uploaded_file($suppliedFile['tmp_name'], $path);
			R::store($dbPost);

			$this->context->transport->success = true;
		}
	}



	/**
	* @route /post/edit/{id}
	*/
	public function editAction($id)
	{
		$post = self::locatePost($id);
		R::preload($post, ['uploader' => 'user']);
		$edited = false;
		$secondary = $post->uploader->id == $this->context->user->id ? 'own' : 'all';


		/* safety */
		$suppliedSafety = InputHelper::get('safety');
		if ($suppliedSafety !== null)
		{
			PrivilegesHelper::confirmWithException($this->context->user, Privilege::EditPostSafety, $secondary);
			$suppliedSafety = intval($suppliedSafety);
			if (!in_array($suppliedSafety, PostSafety::getAll()))
				throw new SimpleException('Invalid safety type "' . $suppliedSafety . '"');
			$post->safety = $suppliedSafety;
			$edited = true;
		}


		/* tags */
		$suppliedTags = InputHelper::get('tags');
		if ($suppliedTags !== null)
		{
			PrivilegesHelper::confirmWithException($this->context->user, Privilege::EditPostTags, $secondary);
			$currentToken = self::serializeTags($post);
			if (InputHelper::get('tags-token') != $currentToken)
				throw new SimpleException('Someone else has changed the tags in the meantime');

			$suppliedTags = preg_split('/[,;\s+]/', $suppliedTags);
			$suppliedTags = array_filter($suppliedTags);
			$suppliedTags = array_unique($suppliedTags);
			foreach ($suppliedTags as $tag)
				if (!preg_match('/^[a-zA-Z0-9_-]+$/i', $tag))
					throw new SimpleException('Invalid tag "' . $tag . '"');
			if (empty($suppliedTags))
				throw new SimpleException('No tags set');

			$dbTags = [];
			foreach ($suppliedTags as $tag)
			{
				$dbTag = R::findOne('tag', 'name = ?', [$tag]);
				if (!$dbTag)
				{
					$dbTag = R::dispense('tag');
					$dbTag->name = $tag;
					R::store($dbTag);
				}
				$dbTags []= $dbTag;
			}

			$post->sharedTag = $dbTags;
			$edited = true;
		}


		/* thumbnail */
		if (isset($_FILES['thumb']))
		{
			PrivilegesHelper::confirmWithException($this->context->user, Privilege::EditPostThumb, $secondary);
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

			$path = $this->config->main->thumbsPath . DS . $post->name;
			move_uploaded_file($suppliedFile['tmp_name'], $path);
		}


		/* db storage */
		if ($edited)
			R::store($post);
		$this->context->transport->success = true;
	}



	/**
	* @route /post/hide/{id}
	*/
	public function hideAction($id)
	{
		$post = self::locatePost($id);
		$secondary = $post->uploader->id == $this->context->user->id ? 'own' : 'all';
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::HidePost, $secondary);
		$post->hidden = true;
		R::store($post);
		$this->context->transport->success = true;
	}

	/**
	* @route /post/unhide/{id}
	*/
	public function unhideAction($id)
	{
		$post = self::locatePost($id);
		$secondary = $post->uploader->id == $this->context->user->id ? 'own' : 'all';
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::HidePost, $secondary);
		$post->hidden = false;
		R::store($post);
		$this->context->transport->success = true;
	}

	/**
	* @route /post/delete/{id}
	*/
	public function deleteAction($id)
	{
		$post = self::locatePost($id);
		$secondary = $post->uploader->id == $this->context->user->id ? 'own' : 'all';
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::DeletePost, $secondary);
		//remove stuff from auxiliary tables
		$post->ownFavoritee = [];
		$post->sharedTag = [];
		R::store($post);
		R::trash($post);
		$this->context->transport->success = true;
	}



	/**
	* @route /post/add-fav/{id}
	* @route /post/fav-add/{id}
	*/
	public function addFavoriteAction($id)
	{
		$post = self::locatePost($id);
		R::preload($post, ['favoritee' => 'user']);

		if (!$this->context->loggedIn)
			throw new SimpleException('Not logged in');

		foreach ($post->via('favoritee')->sharedUser as $fav)
			if ($fav->id == $this->context->user->id)
				throw new SimpleException('Already in favorites');

		PrivilegesHelper::confirmWithException($this->context->user, Privilege::FavoritePost);
		$post->link('favoritee')->user = $this->context->user;
		R::store($post);
		$this->context->transport->success = true;
	}

	/**
	* @route /post/rem-fav/{id}
	* @route /post/fav-rem/{id}
	*/
	public function remFavoriteAction($id)
	{
		$post = self::locatePost($id);
		R::preload($post, ['favoritee' => 'user']);

		PrivilegesHelper::confirmWithException($this->context->user, Privilege::FavoritePost);
		if (!$this->context->loggedIn)
			throw new SimpleException('Not logged in');

		$finalKey = null;
		foreach ($post->ownFavoritee as $key => $fav)
			if ($fav->user->id == $this->context->user->id)
				$finalKey = $key;

		if ($finalKey === null)
			throw new SimpleException('Not in favorites');

		unset ($post->ownFavoritee[$key]);
		R::store($post);
		$this->context->transport->success = true;
	}



	/**
	* Action that decorates the page containing the post.
	* @route /post/{id}
	*/
	public function viewAction($id)
	{
		$post = self::locatePost($id);
		R::preload($post, ['favoritee' => 'user', 'uploader' => 'user', 'tag']);

		if ($post->hidden)
			PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewPost, 'hidden');
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewPost);
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewPost, PostSafety::toString($post->safety));

		$buildNextPostQuery = function($dbQuery, $id, $next)
		{
			$dbQuery->select('id')
				->from('post')
				->where($next ? 'id > ?' : 'id < ?')
				->put($id);
			if (!PrivilegesHelper::confirm($this->context->user, Privilege::ListPosts, 'hidden'))
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

		$dbQuery = R::$f->begin();
		$dbQuery->select('tag.name, COUNT(1) AS count');
		$dbQuery->from('tag');
		$dbQuery->innerJoin('post_tag');
		$dbQuery->on('tag.id = post_tag.tag_id');
		$dbQuery->where('tag.id IN (' . R::genSlots($post->sharedTag) . ')');
		foreach ($post->sharedTag as $tag)
			$dbQuery->put($tag->id);
		$dbQuery->groupBy('tag.id');
		$rows = $dbQuery->get();
		$this->context->transport->tagDistribution = [];
		foreach ($rows as $row)
			$this->context->transport->tagDistribution[$row['name']] = $row['count'];

		$this->context->stylesheets []= 'post-view.css';
		$this->context->scripts []= 'post-view.js';
		$this->context->subTitle = 'showing @' . $post->id;
		$this->context->favorite = $favorite;
		$this->context->transport->post = $post;
		$this->context->transport->prevPostId = $prevPost ? $prevPost['id'] : null;
		$this->context->transport->nextPostId = $nextPost ? $nextPost['id'] : null;
		$this->context->transport->tagsToken = self::serializeTags($post);
	}



	/**
	* Action that renders the thumbnail of the requested file and sends it to user.
	* @route /post/thumb/{id}
	*/
	public function thumbAction($id)
	{
		$this->context->layoutName = 'layout-file';
		$post = self::locatePost($id);

		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewPost);
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewPost, PostSafety::toString($post->safety));

		$path = $this->config->main->thumbsPath . DS . $post->name;
		if (!file_exists($path))
		{
			$srcPath = $this->config->main->filesPath . DS . $post->name;
			$dstPath = $path;
			$dstWidth = $this->config->browsing->thumbWidth;
			$dstHeight = $this->config->browsing->thumbHeight;

			switch ($post->mime_type)
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
					$path = $this->config->main->mediaPath . DS . 'img' . DS . 'thumb-swf.png';
					break;
				default:
					$path = $this->config->main->mediaPath . DS . 'img' . DS . 'thumb.png';
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

				imagepng($dstImage, $dstPath);
				imagedestroy($srcImage);
				imagedestroy($dstImage);
			}
		}
		if (!is_readable($path))
			throw new SimpleException('Thumbnail file is not readable');

		\Chibi\HeadersHelper::set('Pragma', 'public');
		\Chibi\HeadersHelper::set('Cache-Control', 'max-age=86400');
		\Chibi\HeadersHelper::set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

		$this->context->transport->mimeType = 'image/png';
		$this->context->transport->filePath = $path;
	}



	/**
	* Action that renders the requested file itself and sends it to user.
	* @route /post/retrieve/{name}
	*/
	public function retrieveAction($name)
	{
		$this->context->layoutName = 'layout-file';
		$post = self::locatePost($name);

		PrivilegesHelper::confirmWithException($this->context->user, Privilege::RetrievePost);
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::RetrievePost, PostSafety::toString($post->safety));

		$path = $this->config->main->filesPath . DS . $post->name;
		if (!file_exists($path))
			throw new SimpleException('Post file does not exist');
		if (!is_readable($path))
			throw new SimpleException('Post file is not readable');

		$this->context->transport->mimeType = $post->mimeType;
		$this->context->transport->filePath = $path;
	}
}
