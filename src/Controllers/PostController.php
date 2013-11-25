<?php
class PostController
{
	public function workWrapper($callback)
	{
		$this->context->stylesheets []= '../lib/tagit/jquery.tagit.css';
		$this->context->scripts []= '../lib/tagit/jquery.tagit.js';
		$callback();
	}

	private static function serializePost($post)
	{
		$x = [];
		foreach ($post->sharedTag as $tag)
			$x []= TextHelper::reprTag($tag->name);
		foreach ($post->via('crossref')->sharedPost as $relatedPost)
			$x []= TextHelper::reprPost($relatedPost);
		$x []= $post->safety;
		$x []= $post->source;
		$x []= $post->file_hash;
		natcasesort($x);
		$x = join(' ', $x);
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
		$this->context->viewName = 'post-list-wrapper';
		$this->context->stylesheets []= 'post-small.css';
		$this->context->stylesheets []= 'post-list.css';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->stylesheets []= 'paginator.css';
		$this->context->scripts []= 'post-list.js';
		if ($this->context->user->hasEnabledEndlessScrolling())
			$this->context->scripts []= 'paginator-endless.js';
		$this->context->source = $source;
		$this->context->additionalInfo = $additionalInfo;

		//redirect requests in form of /posts/?query=... to canonical address
		$formQuery = InputHelper::get('query');
		if ($formQuery !== null)
		{
			$this->context->transport->searchQuery = $formQuery;
			$this->context->transport->lastSearchQuery = $formQuery;
			if (strpos($formQuery, '/') !== false)
				throw new SimpleException('Search query contains invalid characters');
			$url = \Chibi\UrlHelper::route('post', 'list', ['source' => $source, 'additionalInfo' => $additionalInfo, 'query' => $formQuery]);
			\Chibi\UrlHelper::forward($url);
			return;
		}

		$query = trim($query);
		$page = intval($page);
		$postsPerPage = intval($this->config->browsing->postsPerPage);
		$this->context->subTitle = 'posts';
		$this->context->transport->searchQuery = $query;
		$this->context->transport->lastSearchQuery = $query;
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
	* @route /post/{id}/toggle-tag/{tag}/{enable}
	* @validate tag [^\/]*
	* @validate enable 0|1
	*/
	public function toggleTagAction($id, $tag, $enable)
	{
		$post = Model_Post::locate($id);
		$this->context->transport->post = $post;

		$tagRow = Model_Tag::locate($tag, false);
		if ($tagRow !== null)
			$tag = $tagRow->name;

		if (InputHelper::get('submit'))
		{
			PrivilegesHelper::confirmWithException(Privilege::MassTag);
			$tags = array_map(function($x) { return $x->name; }, $post->sharedTag);

			if (!$enable and in_array($tag, $tags))
			{
				$tags = array_diff($tags, [$tag]);
				LogHelper::log('{user} untagged {post} with {tag}', ['post' => TextHelper::reprPost($post), 'tag' => TextHelper::reprTag($tag)]);
			}
			elseif ($enable)
			{
				$tags += [$tag];
				LogHelper::log('{user} tagged {post} with {tag}', ['post' => TextHelper::reprPost($post), 'tag' => TextHelper::reprTag($tag)]);
			}

			$dbTags = Model_Tag::insertOrUpdate($tags);
			$post->sharedTag = $dbTags;

			Model_Post::save($post);
			StatusHelper::success();
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
	}



	/**
	* @route /random
	* @route /random/{page}
	* @validate page \d*
	*/
	public function randomAction($page = 1)
	{
		$this->listAction('order:random', $page);
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
			R::transaction(function()
			{
				$post = Model_Post::create();
				LogHelper::bufferChanges();

				//basic stuff
				$anonymous = InputHelper::get('anonymous');
				if ($this->context->loggedIn and !$anonymous)
					$post->uploader = $this->context->user;

				//store the post to get the ID in the logs
				Model_Post::save($post);

				//do the edits
				$this->doEdit($post, true);

				//this basically means that user didn't specify file nor url
				if (empty($post->type))
					throw new SimpleException('No post type detected; upload faled');

				//clean edit log
				LogHelper::setBuffer([]);

				//log
				$fmt = ($anonymous and !$this->config->misc->logAnonymousUploads)
					? '{anon}'
					: '{user}';
				$fmt .= ' added {post} (tags: {tags}, safety: {safety}, source: {source})';
				LogHelper::log($fmt, [
					'post' => TextHelper::reprPost($post),
					'tags' => join(', ', array_map(['TextHelper', 'reprTag'], $post->sharedTag)),
					'safety' => PostSafety::toString($post->safety),
					'source' => $post->source]);

				//finish
				LogHelper::flush();
				Model_Post::save($post);
			});

			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/edit
	*/
	public function editAction($id)
	{
		$post = Model_Post::locate($id);
		$this->context->transport->post = $post;

		if (InputHelper::get('submit'))
		{
			$editToken = InputHelper::get('edit-token');
			if ($editToken != self::serializePost($post))
				throw new SimpleException('This post was already edited by someone else in the meantime');

			LogHelper::bufferChanges();
			$this->doEdit($post, false);
			LogHelper::flush();

			Model_Post::save($post);
			Model_Tag::removeUnused();

			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/flag
	*/
	public function flagAction($id)
	{
		$post = Model_Post::locate($id);
		PrivilegesHelper::confirmWithException(Privilege::FlagPost);

		if (InputHelper::get('submit'))
		{
			$key = TextHelper::reprPost($post);

			$flagged = SessionHelper::get('flagged', []);
			if (in_array($key, $flagged))
				throw new SimpleException('You already flagged this post');
			$flagged []= $key;
			SessionHelper::set('flagged', $flagged);

			LogHelper::log('{user} flagged {post} for moderator attention', ['post' => TextHelper::reprPost($post)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/hide
	*/
	public function hideAction($id)
	{
		$post = Model_Post::locate($id);
		PrivilegesHelper::confirmWithException(Privilege::HidePost, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));

		if (InputHelper::get('submit'))
		{
			$post->setHidden(true);
			Model_Post::save($post);

			LogHelper::log('{user} hidden {post}', ['post' => TextHelper::reprPost($post)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/unhide
	*/
	public function unhideAction($id)
	{
		$post = Model_Post::locate($id);
		PrivilegesHelper::confirmWithException(Privilege::HidePost, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));

		if (InputHelper::get('submit'))
		{
			$post->setHidden(false);
			Model_Post::save($post);

			LogHelper::log('{user} unhidden {post}', ['post' => TextHelper::reprPost($post)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/delete
	*/
	public function deleteAction($id)
	{
		$post = Model_Post::locate($id);
		PrivilegesHelper::confirmWithException(Privilege::DeletePost, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));

		if (InputHelper::get('submit'))
		{
			Model_Post::remove($post);

			LogHelper::log('{user} deleted {post}', ['post' => TextHelper::reprPost($id)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/add-fav
	* @route /post/{id}/fav-add
	*/
	public function addFavoriteAction($id)
	{
		$post = Model_Post::locate($id);
		PrivilegesHelper::confirmWithException(Privilege::FavoritePost);

		if (InputHelper::get('submit'))
		{
			if (!$this->context->loggedIn)
				throw new SimpleException('Not logged in');

			$this->context->user->addToFavorites($post);
			Model_User::save($this->context->user);
			StatusHelper::success();
		}
	}

	/**
	* @route /post/{id}/rem-fav
	* @route /post/{id}/fav-rem
	*/
	public function remFavoriteAction($id)
	{
		$post = Model_Post::locate($id);
		PrivilegesHelper::confirmWithException(Privilege::FavoritePost);

		if (InputHelper::get('submit'))
		{
			if (!$this->context->loggedIn)
				throw new SimpleException('Not logged in');

			$this->context->user->remFromFavorites($post);
			Model_User::save($this->context->user);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/score/{score}
	* @validate score -1|0|1
	*/
	public function scoreAction($id, $score)
	{
		$post = Model_Post::locate($id);
		PrivilegesHelper::confirmWithException(Privilege::ScorePost);

		if (InputHelper::get('submit'))
		{
			if (!$this->context->loggedIn)
				throw new SimpleException('Not logged in');

			$this->context->user->score($post, $score);
			Model_User::save($this->context->user);
			StatusHelper::success();
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
		Model_Property::set(Model_Property::FeaturedPostDate, time());
		Model_Property::set(Model_Property::FeaturedPostUserName, $this->context->user->name);
		StatusHelper::success();
		LogHelper::log('{user} featured {post} on main page', ['post' => TextHelper::reprPost($post)]);
	}



	/**
	* Action that decorates the page containing the post.
	* @route /post/{id}
	*/
	public function viewAction($id)
	{
		$post = Model_Post::locate($id);
		R::preload($post, [
			'tag',
			'comment',
			'ownComment.commenter' => 'user']);

		$this->context->transport->lastSearchQuery = InputHelper::get('last-search-query');

		if ($post->hidden)
			PrivilegesHelper::confirmWithException(Privilege::ViewPost, 'hidden');
		PrivilegesHelper::confirmWithException(Privilege::ViewPost);
		PrivilegesHelper::confirmWithException(Privilege::ViewPost, PostSafety::toString($post->safety));

		Model_Post_QueryBuilder::enableTokenLimit(false);
		$prevPostQuery = $this->context->transport->lastSearchQuery . ' prev:' . $id;
		$nextPostQuery = $this->context->transport->lastSearchQuery . ' next:' . $id;
		$prevPost = current(Model_Post::getEntities($prevPostQuery, 1, 1));
		$nextPost = current(Model_Post::getEntities($nextPostQuery, 1, 1));
		Model_Post_QueryBuilder::enableTokenLimit(true);

		$favorite = $this->context->user->hasFavorited($post);
		$score = $this->context->user->getScore($post);
		$flagged = in_array(TextHelper::reprPost($post), SessionHelper::get('flagged', []));

		$this->context->pageThumb = \Chibi\UrlHelper::route('post', 'thumb', ['name' => $post->name]);
		$this->context->stylesheets []= 'post-view.css';
		$this->context->stylesheets []= 'comment-small.css';
		$this->context->scripts []= 'post-view.js';
		$this->context->subTitle = 'showing @' . $post->id . ' &ndash; ' . join(', ', array_map(function($x) { return $x['name']; }, $post->sharedTag));
		$this->context->favorite = $favorite;
		$this->context->score = $score;
		$this->context->flagged = $flagged;
		$this->context->transport->post = $post;
		$this->context->transport->prevPostId = $prevPost ? $prevPost['id'] : null;
		$this->context->transport->nextPostId = $nextPost ? $nextPost['id'] : null;
		$this->context->transport->editToken = self::serializePost($post);
	}



	/**
	* Action that renders the thumbnail of the requested file and sends it to user.
	* @route /post/{name}/thumb
	*/
	public function thumbAction($name, $width = null, $height = null)
	{
		$path = Model_Post::getThumbCustomPath($name, $width, $height);
		if (!file_exists($path))
		{
			$path = Model_Post::getThumbDefaultPath($name, $width, $height);
			if (!file_exists($path))
			{
				$post = Model_Post::locate($name);
				PrivilegesHelper::confirmWithException(Privilege::ListPosts);
				PrivilegesHelper::confirmWithException(Privilege::ListPosts, PostSafety::toString($post->safety));
				$post->makeThumb($width, $height);
				if (!file_exists($path))
					$path = TextHelper::absolutePath($this->config->main->mediaPath . DS . 'img' . DS . 'thumb.jpg');
			}
		}

		if (!is_readable($path))
			throw new SimpleException('Thumbnail file is not readable');

		$this->context->layoutName = 'layout-file';
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

		PrivilegesHelper::confirmWithException(Privilege::RetrievePost);
		PrivilegesHelper::confirmWithException(Privilege::RetrievePost, PostSafety::toString($post->safety));

		$path = TextHelper::absolutePath($this->config->main->filesPath . DS . $post->name);
		if (!file_exists($path))
			throw new SimpleException('Post file does not exist');
		if (!is_readable($path))
			throw new SimpleException('Post file is not readable');

		$ext = substr($post->orig_name, strrpos($post->orig_name, '.') + 1);
		if (strpos($post->orig_name, '.') === false)
			$ext = '.dat';
		$fn = sprintf('%s_%s_%s.%s',
			$this->config->main->title,
			$post->id,
			join(',', array_map(function($tag) { return $tag->name; }, $post->sharedTag)),
			$ext);
		$fn = preg_replace('/[[:^print:]]/', '', $fn);

		$ttl = 60 * 60 * 24 * 14;

		$this->context->transport->cacheDaysToLive = 14;
		$this->context->transport->customFileName = $fn;
		$this->context->transport->mimeType = $post->mimeType;
		$this->context->transport->fileHash = 'post' . $post->file_hash;
		$this->context->transport->filePath = $path;
	}



	private function doEdit($post, $isNew)
	{
		/* file contents */
		if (!empty($_FILES['file']['name']))
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostFile, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));

			$suppliedFile = $_FILES['file'];
			self::handleUploadErrors($suppliedFile);

			$srcPath = $suppliedFile['tmp_name'];
			$post->setContentFromPath($srcPath);

			if (!$isNew)
				LogHelper::log('{user} changed contents of {post}', ['post' => TextHelper::reprPost($post)]);
		}
		elseif (InputHelper::get('url'))
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostFile, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));

			$url = InputHelper::get('url');
			$post->setContentFromUrl($url);

			if (!$isNew)
				LogHelper::log('{user} changed contents of {post}', ['post' => TextHelper::reprPost($post)]);
		}

		/* safety */
		$suppliedSafety = InputHelper::get('safety');
		if ($suppliedSafety !== null)
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostSafety, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));

			$oldSafety = $post->safety;
			$post->setSafety($suppliedSafety);
			$newSafety = $post->safety;

			if ($oldSafety != $newSafety)
				LogHelper::log('{user} changed safety of {post} to {safety}', ['post' => TextHelper::reprPost($post), 'safety' => PostSafety::toString($post->safety)]);
		}

		/* tags */
		$suppliedTags = InputHelper::get('tags');
		if ($suppliedTags !== null)
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostTags, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));

			$oldTags = array_map(function($tag) { return $tag->name; }, $post->sharedTag);
			$post->setTagsFromText($suppliedTags);
			$newTags = array_map(function($tag) { return $tag->name; }, $post->sharedTag);

			foreach (array_diff($oldTags, $newTags) as $tag)
				LogHelper::log('{user} untagged {post} with {tag}', ['post' => TextHelper::reprPost($post), 'tag' => TextHelper::reprTag($tag)]);

			foreach (array_diff($newTags, $oldTags) as $tag)
				LogHelper::log('{user} tagged {post} with {tag}', ['post' => TextHelper::reprPost($post), 'tag' => TextHelper::reprTag($tag)]);
		}

		/* source */
		$suppliedSource = InputHelper::get('source');
		if ($suppliedSource !== null)
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostSource, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));

			$oldSource = $post->source;
			$post->setSource($suppliedSource);
			$newSource = $post->source;

			if ($oldSource != $newSource)
				LogHelper::log('{user} changed source of {post} to {source}', ['post' => TextHelper::reprPost($post), 'source' => $post->source]);
		}

		/* relations */
		$suppliedRelations = InputHelper::get('relations');
		if ($suppliedRelations !== null)
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostRelations, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));

			$oldRelatedIds = array_map(function($post) { return $post->id; }, $post->via('crossref')->sharedPost);
			$post->setRelationsFromText($suppliedRelations);
			$newRelatedIds = array_map(function($post) { return $post->id; }, $post->via('crossref')->sharedPost);

			foreach (array_diff($oldRelatedIds, $newRelatedIds) as $post2id)
				LogHelper::log('{user} removed relation between {post} and {post2}', ['post' => TextHelper::reprPost($post), 'post2' => TextHelper::reprPost($post2id)]);

			foreach (array_diff($newRelatedIds, $oldRelatedIds) as $post2id)
				LogHelper::log('{user} added relation between {post} and {post2}', ['post' => TextHelper::reprPost($post), 'post2' => TextHelper::reprPost($post2id)]);
		}

		/* thumbnail */
		if (!empty($_FILES['thumb']['name']))
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostThumb, PrivilegesHelper::getIdentitySubPrivilege($post->uploader));

			$suppliedFile = $_FILES['thumb'];
			self::handleUploadErrors($suppliedFile);

			$srcPath = $suppliedFile['tmp_name'];
			$post->setCustomThumbnailFromPath($srcPath);

			LogHelper::log('{user} changed thumb of {post}', ['post' => TextHelper::reprPost($post)]);
		}
	}
}
