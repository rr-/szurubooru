<?php
class PostController
{
	public function workWrapper($callback)
	{
		$this->context->stylesheets []= '../lib/tagit/jquery.tagit.css';
		$this->context->scripts []= '../lib/tagit/jquery.tagit.js';
		$callback();
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
		if ($this->config->browsing->endlessScrolling)
			$this->context->scripts []= 'post-list-endless.js';

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
		$searchDbQuery->orderBy('upload_date DESC');
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
			$suppliedSafety = intval(InputHelper::get('safety'));
			if (!in_array($suppliedSafety, PostSafety::getAll()))
				throw new SimpleException('Invalid safety type "' . $suppliedSafety . '"');

			$suppliedTags = InputHelper::get('tags');
			$suppliedTags = preg_split('/[,;\s+]/', $suppliedTags);
			$suppliedTags = array_filter($suppliedTags);
			$suppliedTags = array_unique($suppliedTags);
			foreach ($suppliedTags as $tag)
				if (!preg_match('/^[a-zA-Z0-9_-]+$/i', $tag))
					throw new SimpleException('Invalid tag "' . $tag . '"');
			if (empty($suppliedTags))
				throw new SimpleException('No tags set');

			$suppliedFile = $_FILES['file'];
			switch ($suppliedFile['error'])
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
					throw new SimpleException('Generic file upload error (id: ' . $suppliedFile['error'] . ')');
			}
			if (!is_uploaded_file($suppliedFile['tmp_name']))
				throw new SimpleException('Generic file upload error');

			#$mimeType = $suppliedFile['type'];
			$mimeType = mime_content_type($suppliedFile['tmp_name']);
			switch ($mimeType)
			{
				case 'image/gif':
				case 'image/png':
				case 'image/jpeg':
					$postType = PostType::Image;
					break;
				case 'application/x-shockwave-flash':
					$postType = PostType::Flash;
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

			$dbPost = R::dispense('post');
			$dbPost->type = $postType;
			$dbPost->name = $name;
			$dbPost->orig_name = basename($suppliedFile['name']);
			$dbPost->file_hash = $fileHash;
			$dbPost->mime_type = $mimeType;
			$dbPost->safety = $suppliedSafety;
			$dbPost->upload_date = time();
			$dbPost->sharedTag = $dbTags;
			$dbPost->ownUser = $this->context->user;

			move_uploaded_file($suppliedFile['tmp_name'], $path);
			R::store($dbPost);

			$this->context->transport->success = true;
		}
	}



	/**
	* Action that decorates the page containing the post.
	* @route /post/{id}
	*/
	public function viewAction($id)
	{
		$post = R::findOne('post', 'id = ?', [$id]);
		if (!$post)
			throw new SimpleException('Invalid post ID "' . $id . '"');

		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewPost);
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewPost, PostSafety::toString($post->safety));

		$this->context->stylesheets []= 'post-view.css';
		$this->context->subTitle = 'showing @' . $post->id;
		$this->context->transport->post = $post;
	}



	/**
	* Action that renders the thumbnail of the requested file and sends it to user.
	* @route /post/thumb/{id}
	*/
	public function thumbAction($id)
	{
		$this->context->layoutName = 'layout-file';

		$post = R::findOne('post', 'id = ?', [$id]);
		if (!$post)
			throw new SimpleException('Invalid post ID "' . $id . '"');

		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewPost);
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewPost, PostSafety::toString($post->safety));

		$path = $this->config->main->thumbsPath . DS . $post->name . '.png';
		if (!file_exists($path))
		{
			$srcPath = $this->config->main->thumbsPath . DS . $post->name;
			$dstPath = $path;
			$dstWidth = $this->config->browsing->thumbWidth;
			$dstHeight = $this->config->browsing->thumbHeight;

			switch($post->mime_type)
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

		$post = R::findOne('post', 'name = ?', [$name]);
		if (!$post)
			throw new SimpleException('Invalid post name "' . $name . '"');

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
}
