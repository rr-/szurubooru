<?php
class PostController
{
	public function listAction($query = null, $page = 1, $source = 'posts', $additionalInfo = null)
	{
		$context = getContext();
		$context->viewName = 'post-list-wrapper';
		$context->source = $source;
		$context->additionalInfo = $additionalInfo;
		$context->handleExceptions = true;

		//redirect requests in form of /posts/?query=... to canonical address
		$formQuery = InputHelper::get('query');
		if ($formQuery !== null)
		{
			$context->transport->searchQuery = $formQuery;
			$context->transport->lastSearchQuery = $formQuery;
			if (strpos($formQuery, '/') !== false)
				throw new SimpleException('Search query contains invalid characters');

			$url = \Chibi\Router::linkTo(['PostController', 'listAction'], [
				'source' => $source,
				'additionalInfo' => $additionalInfo,
				'query' => $formQuery]);
			\Chibi\Util\Url::forward($url);
			return;
		}

		$query = trim($query);
		$page = max(1, intval($page));
		$postsPerPage = intval(getConfig()->browsing->postsPerPage);
		$context->transport->searchQuery = $query;
		$context->transport->lastSearchQuery = $query;
		Access::assert(Privilege::ListPosts);
		if ($source == 'mass-tag')
		{
			Access::assert(Privilege::MassTag);
			$context->massTagTag = $additionalInfo;
			$context->massTagQuery = $query;

			if (!Access::check(Privilege::MassTag, 'all'))
				$query = trim($query . ' submit:' . Auth::getCurrentUser()->name);
		}

		$posts = PostSearchService::getEntities($query, $postsPerPage, $page);
		$postCount = PostSearchService::getEntityCount($query);
		$pageCount = ceil($postCount / $postsPerPage);
		$page = min($pageCount, $page);
		PostModel::preloadTags($posts);

		$context->transport->paginator = new StdClass;
		$context->transport->paginator->page = $page;
		$context->transport->paginator->pageCount = $pageCount;
		$context->transport->paginator->entityCount = $postCount;
		$context->transport->paginator->entities = $posts;
		$context->transport->posts = $posts;
	}

	public function toggleTagAction($id, $tag, $enable)
	{
		$context = getContext();
		$tagName = $tag;
		$post = PostModel::findByIdOrName($id);
		$context->transport->post = $post;

		if (!InputHelper::get('submit'))
			return;

		Access::assert(
			Privilege::MassTag,
			Access::getIdentity($post->getUploader()));

		$tags = $post->getTags();

		if (!$enable)
		{
			foreach ($tags as $i => $tag)
				if ($tag->name == $tagName)
					unset($tags[$i]);

			LogHelper::log('{user} untagged {post} with {tag}', [
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tag)]);
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
			LogHelper::log('{user} tagged {post} with {tag}', [
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tag)]);
		}

		$post->setTags($tags);

		PostModel::save($post);
		StatusHelper::success();
	}

	public function favoritesAction($page = 1)
	{
		$this->listAction('favmin:1', $page);
	}

	public function upvotedAction($page = 1)
	{
		$this->listAction('scoremin:1', $page);
	}

	public function randomAction($page = 1)
	{
		$this->listAction('order:random', $page);
	}

	public function uploadAction()
	{
		$context = getContext();
		Access::assert(Privilege::UploadPost);
		if (getConfig()->registration->needEmailForUploading)
			Access::assertEmailConfirmation();

		if (!InputHelper::get('submit'))
			return;

		\Chibi\Database::transaction(function() use ($context)
		{
			$post = PostModel::spawn();
			LogHelper::bufferChanges();

			//basic stuff
			$anonymous = InputHelper::get('anonymous');
			if (Auth::isLoggedIn() and !$anonymous)
				$post->setUploader(Auth::getCurrentUser());

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
			$fmt = ($anonymous and !getConfig()->misc->logAnonymousUploads)
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

	public function editAction($id)
	{
		$context = getContext();
		$post = PostModel::findByIdOrName($id);
		$context->transport->post = $post;

		if (!InputHelper::get('submit'))
			return;

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

	public function flagAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::FlagPost, Access::getIdentity($post->getUploader()));

		if (!InputHelper::get('submit'))
			return;

		$key = TextHelper::reprPost($post);

		$flagged = SessionHelper::get('flagged', []);
		if (in_array($key, $flagged))
			throw new SimpleException('You already flagged this post');
		$flagged []= $key;
		SessionHelper::set('flagged', $flagged);

		LogHelper::log('{user} flagged {post} for moderator attention', ['post' => TextHelper::reprPost($post)]);
		StatusHelper::success();
	}

	public function hideAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::HidePost, Access::getIdentity($post->getUploader()));

		if (!InputHelper::get('submit'))
			return;

		$post->setHidden(true);
		PostModel::save($post);

		LogHelper::log('{user} hidden {post}', ['post' => TextHelper::reprPost($post)]);
		StatusHelper::success();
	}

	public function unhideAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::HidePost, Access::getIdentity($post->getUploader()));

		if (!InputHelper::get('submit'))
			return;

		$post->setHidden(false);
		PostModel::save($post);

		LogHelper::log('{user} unhidden {post}', ['post' => TextHelper::reprPost($post)]);
		StatusHelper::success();
	}

	public function deleteAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::DeletePost, Access::getIdentity($post->getUploader()));

		if (!InputHelper::get('submit'))
			return;

		PostModel::remove($post);

		LogHelper::log('{user} deleted {post}', ['post' => TextHelper::reprPost($id)]);
		StatusHelper::success();
	}

	public function addFavoriteAction($id)
	{
		$context = getContext();
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::FavoritePost, Access::getIdentity($post->getUploader()));

		if (!InputHelper::get('submit'))
			return;

		if (!Auth::isLoggedIn())
			throw new SimpleException('Not logged in');

		UserModel::updateUserScore(Auth::getCurrentUser(), $post, 1);
		UserModel::addToUserFavorites(Auth::getCurrentUser(), $post);
		StatusHelper::success();
	}

	public function removeFavoriteAction($id)
	{
		$context = getContext();
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::FavoritePost, Access::getIdentity($post->getUploader()));

		if (!InputHelper::get('submit'))
			return;

		if (!Auth::isLoggedIn())
			throw new SimpleException('Not logged in');

		UserModel::removeFromUserFavorites(Auth::getCurrentUser(), $post);
		StatusHelper::success();
	}

	public function scoreAction($id, $score)
	{
		$context = getContext();
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::ScorePost, Access::getIdentity($post->getUploader()));

		if (!InputHelper::get('submit'))
			return;

		if (!Auth::isLoggedIn())
			throw new SimpleException('Not logged in');

		UserModel::updateUserScore(Auth::getCurrentUser(), $post, $score);
		StatusHelper::success();
	}

	public function featureAction($id)
	{
		$context = getContext();
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::FeaturePost, Access::getIdentity($post->getUploader()));
		PropertyModel::set(PropertyModel::FeaturedPostId, $post->id);
		PropertyModel::set(PropertyModel::FeaturedPostDate, time());
		PropertyModel::set(PropertyModel::FeaturedPostUserName, Auth::getCurrentUser()->name);
		StatusHelper::success();
		LogHelper::log('{user} featured {post} on main page', ['post' => TextHelper::reprPost($post)]);
	}

	public function viewAction($id)
	{
		$context = getContext();
		$post = PostModel::findByIdOrName($id);
		CommentModel::preloadCommenters($post->getComments());

		if ($post->hidden)
			Access::assert(Privilege::ViewPost, 'hidden');
		Access::assert(Privilege::ViewPost);
		Access::assert(Privilege::ViewPost, PostSafety::toString($post->safety));

		try
		{
			$context->transport->lastSearchQuery = InputHelper::get('last-search-query');
			list ($prevPostId, $nextPostId) =
				PostSearchService::getPostIdsAround(
					$context->transport->lastSearchQuery, $id);
		}
		#search for some reason was invalid, e.g. tag was deleted in the meantime
		catch (Exception $e)
		{
			$context->transport->lastSearchQuery = '';
			list ($prevPostId, $nextPostId) =
				PostSearchService::getPostIdsAround(
					$context->transport->lastSearchQuery, $id);
		}

		$favorite = Auth::getCurrentUser()->hasFavorited($post);
		$score = Auth::getCurrentUser()->getScore($post);
		$flagged = in_array(TextHelper::reprPost($post), SessionHelper::get('flagged', []));

		$context->favorite = $favorite;
		$context->score = $score;
		$context->flagged = $flagged;
		$context->transport->post = $post;
		$context->transport->prevPostId = $prevPostId ? $prevPostId : null;
		$context->transport->nextPostId = $nextPostId ? $nextPostId : null;
	}

	public function thumbAction($name, $width = null, $height = null)
	{
		$context = getContext();
		$path = PostModel::getThumbCustomPath($name, $width, $height);
		if (!file_exists($path))
		{
			$path = PostModel::getThumbDefaultPath($name, $width, $height);
			if (!file_exists($path))
			{
				$post = PostModel::findByIdOrName($name);
				Access::assert(Privilege::ListPosts);
				Access::assert(Privilege::ListPosts, PostSafety::toString($post->safety));
				$post->generateThumb($width, $height);
				if (!file_exists($path))
				{
					$path = getConfig()->main->mediaPath . DS . 'img' . DS . 'thumb.jpg';
					$path = TextHelper::absolutePath($path);
				}
			}
		}

		if (!is_readable($path))
			throw new SimpleException('Thumbnail file is not readable');

		$context->layoutName = 'layout-file';
		$context->transport->cacheDaysToLive = 365;
		$context->transport->mimeType = 'image/jpeg';
		$context->transport->fileHash = 'thumb' . md5($name . filemtime($path));
		$context->transport->filePath = $path;
	}

	public function retrieveAction($name)
	{
		$post = PostModel::findByName($name, true);
		$config = getConfig();
		$context = getContext();

		Access::assert(Privilege::RetrievePost);
		Access::assert(Privilege::RetrievePost, PostSafety::toString($post->safety));

		$path = $config->main->filesPath . DS . $post->name;
		$path = TextHelper::absolutePath($path);
		if (!file_exists($path))
			throw new SimpleNotFoundException('Post file does not exist');
		if (!is_readable($path))
			throw new SimpleException('Post file is not readable');

		$fn = sprintf('%s_%s_%s.%s',
			$config->main->title,
			$post->id,
			join(',', array_map(function($tag) { return $tag->name; }, $post->getTags())),
			TextHelper::resolveMimeType($post->mimeType) ?: 'dat');
		$fn = preg_replace('/[[:^print:]]/', '', $fn);

		$ttl = 60 * 60 * 24 * 14;

		$context->layoutName = 'layout-file';
		$context->transport->cacheDaysToLive = 14;
		$context->transport->customFileName = $fn;
		$context->transport->mimeType = $post->mimeType;
		$context->transport->fileHash = 'post' . $post->fileHash;
		$context->transport->filePath = $path;
	}

	private function doEdit($post, $isNew)
	{
		/* safety */
		$suppliedSafety = InputHelper::get('safety');
		if ($suppliedSafety !== null)
		{
			if (!$isNew)
				Access::assert(Privilege::EditPostSafety, Access::getIdentity($post->getUploader()));

			$oldSafety = $post->safety;
			$post->setSafety($suppliedSafety);
			$newSafety = $post->safety;

			if ($oldSafety != $newSafety)
			{
				LogHelper::log('{user} changed safety of {post} to {safety}', [
					'post' => TextHelper::reprPost($post),
					'safety' => PostSafety::toString($post->safety)]);
			}
		}

		/* tags */
		$suppliedTags = InputHelper::get('tags');
		if ($suppliedTags !== null)
		{
			if (!$isNew)
				Access::assert(Privilege::EditPostTags, Access::getIdentity($post->getUploader()));

			$oldTags = array_map(function($tag) { return $tag->name; }, $post->getTags());
			$post->setTagsFromText($suppliedTags);
			$newTags = array_map(function($tag) { return $tag->name; }, $post->getTags());

			foreach (array_diff($oldTags, $newTags) as $tag)
			{
				LogHelper::log('{user} untagged {post} with {tag}', [
					'post' => TextHelper::reprPost($post),
					'tag' => TextHelper::reprTag($tag)]);
			}

			foreach (array_diff($newTags, $oldTags) as $tag)
			{
				LogHelper::log('{user} tagged {post} with {tag}', [
					'post' => TextHelper::reprPost($post),
					'tag' => TextHelper::reprTag($tag)]);
			}
		}

		/* source */
		$suppliedSource = InputHelper::get('source');
		if ($suppliedSource !== null)
		{
			if (!$isNew)
				Access::assert(Privilege::EditPostSource, Access::getIdentity($post->getUploader()));

			$oldSource = $post->source;
			$post->setSource($suppliedSource);
			$newSource = $post->source;

			if ($oldSource != $newSource)
			{
				LogHelper::log('{user} changed source of {post} to {source}', [
					'post' => TextHelper::reprPost($post),
					'source' => $post->source]);
			}
		}

		/* relations */
		$suppliedRelations = InputHelper::get('relations');
		if ($suppliedRelations !== null)
		{
			if (!$isNew)
				Access::assert(Privilege::EditPostRelations, Access::getIdentity($post->getUploader()));

			$oldRelatedIds = array_map(function($post) { return $post->id; }, $post->getRelations());
			$post->setRelationsFromText($suppliedRelations);
			$newRelatedIds = array_map(function($post) { return $post->id; }, $post->getRelations());

			foreach (array_diff($oldRelatedIds, $newRelatedIds) as $post2id)
			{
				LogHelper::log('{user} removed relation between {post} and {post2}', [
					'post' => TextHelper::reprPost($post),
					'post2' => TextHelper::reprPost($post2id)]);
			}

			foreach (array_diff($newRelatedIds, $oldRelatedIds) as $post2id)
			{
				LogHelper::log('{user} added relation between {post} and {post2}', [
					'post' => TextHelper::reprPost($post),
					'post2' => TextHelper::reprPost($post2id)]);
			}
		}

		/* file contents */
		if (!empty($_FILES['file']['name']))
		{
			if (!$isNew)
				Access::assert(Privilege::EditPostFile, Access::getIdentity($post->getUploader()));

			$suppliedFile = $_FILES['file'];
			TransferHelper::handleUploadErrors($suppliedFile);

			$post->setContentFromPath($suppliedFile['tmp_name'], $suppliedFile['name']);

			if (!$isNew)
				LogHelper::log('{user} changed contents of {post}', ['post' => TextHelper::reprPost($post)]);
		}
		elseif (InputHelper::get('url'))
		{
			if (!$isNew)
				Access::assert(Privilege::EditPostFile, Access::getIdentity($post->getUploader()));

			$url = InputHelper::get('url');
			$post->setContentFromUrl($url);

			if (!$isNew)
				LogHelper::log('{user} changed contents of {post}', ['post' => TextHelper::reprPost($post)]);
		}

		/* thumbnail */
		if (!empty($_FILES['thumb']['name']))
		{
			if (!$isNew)
				Access::assert(Privilege::EditPostThumb, Access::getIdentity($post->getUploader()));

			$suppliedFile = $_FILES['thumb'];
			TransferHelper::handleUploadErrors($suppliedFile);

			$post->setCustomThumbnailFromPath($srcPath = $suppliedFile['tmp_name']);

			LogHelper::log('{user} changed thumb of {post}', ['post' => TextHelper::reprPost($post)]);
		}
	}
}
