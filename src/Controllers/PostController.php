<?php
class PostController
{
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
		$this->context->source = $source;
		$this->context->additionalInfo = $additionalInfo;
		$this->context->handleExceptions = true;

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
		$page = max(1, intval($page));
		$postsPerPage = intval($this->config->browsing->postsPerPage);
		$this->context->transport->searchQuery = $query;
		$this->context->transport->lastSearchQuery = $query;
		PrivilegesHelper::confirmWithException(Privilege::ListPosts);
		if ($source == 'mass-tag')
		{
			PrivilegesHelper::confirmWithException(Privilege::MassTag);
			$this->context->massTagTag = $additionalInfo;
			$this->context->massTagQuery = $query;

			if (!PrivilegesHelper::confirm(Privilege::MassTag, 'all'))
				$query = trim($query . ' submit:' . $this->context->user->name);
		}

		$posts = PostSearchService::getEntities($query, $postsPerPage, $page);
		$postCount = PostSearchService::getEntityCount($query);
		$pageCount = ceil($postCount / $postsPerPage);
		$page = min($pageCount, $page);
		PostModel::preloadTags($posts);

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
		$tagName = $tag;
		$post = PostModel::findByIdOrName($id);
		$this->context->transport->post = $post;

		if (InputHelper::get('submit'))
		{
			PrivilegesHelper::confirmWithException(Privilege::MassTag, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

			$tags = $post->getTags();

			if (!$enable)
			{
				foreach ($tags as $i => $tag)
					if ($tag->name == $tagName)
						unset($tags[$i]);
				LogHelper::log('{user} untagged {post} with {tag}', ['post' => TextHelper::reprPost($post), 'tag' => TextHelper::reprTag($tag)]);
			}
			elseif ($enable)
			{
				$tag = TagModel::findByName($tagName, false);
				if ($tag === null)
				{
					$tag = TagModel::spawn();
					$tag->name = $tagName;
					TagModel::save($tag);
				}

				$tags []= $tag;
				LogHelper::log('{user} tagged {post} with {tag}', ['post' => TextHelper::reprPost($post), 'tag' => TextHelper::reprTag($tag)]);
			}

			$post->setTags($tags);

			PostModel::save($post);
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
		PrivilegesHelper::confirmWithException(Privilege::UploadPost);
		if ($this->config->registration->needEmailForUploading)
			PrivilegesHelper::confirmEmail($this->context->user);

		if (InputHelper::get('submit'))
		{
			Database::transaction(function()
			{
				$post = PostModel::spawn();
				LogHelper::bufferChanges();

				//basic stuff
				$anonymous = InputHelper::get('anonymous');
				if ($this->context->loggedIn and !$anonymous)
					$post->setUploader($this->context->user);

				//store the post to get the ID in the logs
				PostModel::forgeId($post);

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
					'tags' => TextHelper::reprTags($post->getTags()),
					'safety' => PostSafety::toString($post->safety),
					'source' => $post->source]);

				//finish
				LogHelper::flush();
				PostModel::save($post);
			});

			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/edit
	*/
	public function editAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		$this->context->transport->post = $post;

		if (InputHelper::get('submit'))
		{
			$editToken = InputHelper::get('edit-token');
			if ($editToken != $post->getEditToken())
				throw new SimpleException('This post was already edited by someone else in the meantime');

			LogHelper::bufferChanges();
			$this->doEdit($post, false);
			LogHelper::flush();

			PostModel::save($post);
			TagModel::removeUnused();

			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/flag
	*/
	public function flagAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		PrivilegesHelper::confirmWithException(Privilege::FlagPost, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

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
		$post = PostModel::findByIdOrName($id);
		PrivilegesHelper::confirmWithException(Privilege::HidePost, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

		if (InputHelper::get('submit'))
		{
			$post->setHidden(true);
			PostModel::save($post);

			LogHelper::log('{user} hidden {post}', ['post' => TextHelper::reprPost($post)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/unhide
	*/
	public function unhideAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		PrivilegesHelper::confirmWithException(Privilege::HidePost, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

		if (InputHelper::get('submit'))
		{
			$post->setHidden(false);
			PostModel::save($post);

			LogHelper::log('{user} unhidden {post}', ['post' => TextHelper::reprPost($post)]);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/delete
	*/
	public function deleteAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		PrivilegesHelper::confirmWithException(Privilege::DeletePost, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

		if (InputHelper::get('submit'))
		{
			PostModel::remove($post);

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
		$post = PostModel::findByIdOrName($id);
		PrivilegesHelper::confirmWithException(Privilege::FavoritePost, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

		if (InputHelper::get('submit'))
		{
			if (!$this->context->loggedIn)
				throw new SimpleException('Not logged in');

			UserModel::addToUserFavorites($this->context->user, $post);
			StatusHelper::success();
		}
	}

	/**
	* @route /post/{id}/rem-fav
	* @route /post/{id}/fav-rem
	*/
	public function remFavoriteAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		PrivilegesHelper::confirmWithException(Privilege::FavoritePost, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

		if (InputHelper::get('submit'))
		{
			if (!$this->context->loggedIn)
				throw new SimpleException('Not logged in');

			UserModel::removeFromUserFavorites($this->context->user, $post);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/score/{score}
	* @validate score -1|0|1
	*/
	public function scoreAction($id, $score)
	{
		$post = PostModel::findByIdOrName($id);
		PrivilegesHelper::confirmWithException(Privilege::ScorePost, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

		if (InputHelper::get('submit'))
		{
			if (!$this->context->loggedIn)
				throw new SimpleException('Not logged in');

			UserModel::updateUserScore($this->context->user, $post, $score);
			StatusHelper::success();
		}
	}



	/**
	* @route /post/{id}/feature
	*/
	public function featureAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		PrivilegesHelper::confirmWithException(Privilege::FeaturePost, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));
		PropertyModel::set(PropertyModel::FeaturedPostId, $post->id);
		PropertyModel::set(PropertyModel::FeaturedPostDate, time());
		PropertyModel::set(PropertyModel::FeaturedPostUserName, $this->context->user->name);
		StatusHelper::success();
		LogHelper::log('{user} featured {post} on main page', ['post' => TextHelper::reprPost($post)]);
	}



	/**
	* Action that decorates the page containing the post.
	* @route /post/{id}
	*/
	public function viewAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		CommentModel::preloadCommenters($post->getComments());

		if ($post->hidden)
			PrivilegesHelper::confirmWithException(Privilege::ViewPost, 'hidden');
		PrivilegesHelper::confirmWithException(Privilege::ViewPost);
		PrivilegesHelper::confirmWithException(Privilege::ViewPost, PostSafety::toString($post->safety));

		PostSearchService::enableTokenLimit(false);
		try
		{
			$this->context->transport->lastSearchQuery = InputHelper::get('last-search-query');
			$prevPostQuery = $this->context->transport->lastSearchQuery . ' prev:' . $id;
			$nextPostQuery = $this->context->transport->lastSearchQuery . ' next:' . $id;
			$prevPost = current(PostSearchService::getEntities($prevPostQuery, 1, 1));
			$nextPost = current(PostSearchService::getEntities($nextPostQuery, 1, 1));
		}
		#search for some reason was invalid, e.g. tag was deleted in the meantime
		catch (Exception $e)
		{
			$this->context->transport->lastSearchQuery = '';
			$prevPost = current(PostSearchService::getEntities('prev:' . $id, 1, 1));
			$nextPost = current(PostSearchService::getEntities('next:' . $id, 1, 1));
		}
		PostSearchService::enableTokenLimit(true);

		$favorite = $this->context->user->hasFavorited($post);
		$score = $this->context->user->getScore($post);
		$flagged = in_array(TextHelper::reprPost($post), SessionHelper::get('flagged', []));

		$this->context->favorite = $favorite;
		$this->context->score = $score;
		$this->context->flagged = $flagged;
		$this->context->transport->post = $post;
		$this->context->transport->prevPostId = $prevPost ? $prevPost->id : null;
		$this->context->transport->nextPostId = $nextPost ? $nextPost->id : null;
	}



	/**
	* Action that renders the thumbnail of the requested file and sends it to user.
	* @route /post/{name}/thumb
	*/
	public function thumbAction($name, $width = null, $height = null)
	{
		$path = PostModel::getThumbCustomPath($name, $width, $height);
		if (!file_exists($path))
		{
			$path = PostModel::getThumbDefaultPath($name, $width, $height);
			if (!file_exists($path))
			{
				$post = PostModel::findByIdOrName($name);
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
		$this->context->transport->cacheDaysToLive = 365;
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
		$post = PostModel::findByName($name, true);

		PrivilegesHelper::confirmWithException(Privilege::RetrievePost);
		PrivilegesHelper::confirmWithException(Privilege::RetrievePost, PostSafety::toString($post->safety));

		$path = TextHelper::absolutePath($this->config->main->filesPath . DS . $post->name);
		if (!file_exists($path))
			throw new SimpleNotFoundException('Post file does not exist');
		if (!is_readable($path))
			throw new SimpleException('Post file is not readable');

		$fn = sprintf('%s_%s_%s.%s',
			$this->config->main->title,
			$post->id,
			join(',', array_map(function($tag) { return $tag->name; }, $post->getTags())),
			TextHelper::resolveMimeType($post->mimeType) ?: 'dat');
		$fn = preg_replace('/[[:^print:]]/', '', $fn);

		$ttl = 60 * 60 * 24 * 14;

		$this->context->layoutName = 'layout-file';
		$this->context->transport->cacheDaysToLive = 14;
		$this->context->transport->customFileName = $fn;
		$this->context->transport->mimeType = $post->mimeType;
		$this->context->transport->fileHash = 'post' . $post->fileHash;
		$this->context->transport->filePath = $path;
	}



	private function doEdit($post, $isNew)
	{
		/* file contents */
		if (!empty($_FILES['file']['name']))
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostFile, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

			$suppliedFile = $_FILES['file'];
			self::handleUploadErrors($suppliedFile);

			$srcPath = $suppliedFile['tmp_name'];
			$post->setContentFromPath($srcPath);
			$post->origName = $suppliedFile['name'];

			if (!$isNew)
				LogHelper::log('{user} changed contents of {post}', ['post' => TextHelper::reprPost($post)]);
		}
		elseif (InputHelper::get('url'))
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostFile, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

			$url = InputHelper::get('url');
			$post->setContentFromUrl($url);
			$post->origName = $url;

			if (!$isNew)
				LogHelper::log('{user} changed contents of {post}', ['post' => TextHelper::reprPost($post)]);
		}

		/* safety */
		$suppliedSafety = InputHelper::get('safety');
		if ($suppliedSafety !== null)
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostSafety, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

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
				PrivilegesHelper::confirmWithException(Privilege::EditPostTags, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

			$oldTags = array_map(function($tag) { return $tag->name; }, $post->getTags());
			$post->setTagsFromText($suppliedTags);
			$newTags = array_map(function($tag) { return $tag->name; }, $post->getTags());

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
				PrivilegesHelper::confirmWithException(Privilege::EditPostSource, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

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
				PrivilegesHelper::confirmWithException(Privilege::EditPostRelations, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

			$oldRelatedIds = array_map(function($post) { return $post->id; }, $post->getRelations());
			$post->setRelationsFromText($suppliedRelations);
			$newRelatedIds = array_map(function($post) { return $post->id; }, $post->getRelations());

			foreach (array_diff($oldRelatedIds, $newRelatedIds) as $post2id)
				LogHelper::log('{user} removed relation between {post} and {post2}', ['post' => TextHelper::reprPost($post), 'post2' => TextHelper::reprPost($post2id)]);

			foreach (array_diff($newRelatedIds, $oldRelatedIds) as $post2id)
				LogHelper::log('{user} added relation between {post} and {post2}', ['post' => TextHelper::reprPost($post), 'post2' => TextHelper::reprPost($post2id)]);
		}

		/* thumbnail */
		if (!empty($_FILES['thumb']['name']))
		{
			if (!$isNew)
				PrivilegesHelper::confirmWithException(Privilege::EditPostThumb, PrivilegesHelper::getIdentitySubPrivilege($post->getUploader()));

			$suppliedFile = $_FILES['thumb'];
			self::handleUploadErrors($suppliedFile);

			$srcPath = $suppliedFile['tmp_name'];
			$post->setCustomThumbnailFromPath($srcPath);

			LogHelper::log('{user} changed thumb of {post}', ['post' => TextHelper::reprPost($post)]);
		}
	}
}
