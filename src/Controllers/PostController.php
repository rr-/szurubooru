<?php
class PostController
{
	public function listView($query = null, $page = 1, $source = 'posts', $additionalInfo = null)
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

			$url = \Chibi\Router::linkTo(['PostController', 'listView'], [
				'source' => $source,
				'additionalInfo' => $additionalInfo,
				'query' => $formQuery]);
			\Chibi\Util\Url::forward($url);
			return;
		}

		$query = trim($query);
		$context->transport->searchQuery = $query;
		$context->transport->lastSearchQuery = $query;
		if ($source == 'mass-tag')
		{
			Access::assert(Privilege::MassTag);
			$context->massTagTag = $additionalInfo;
			$context->massTagQuery = $query;

			if (!Access::check(Privilege::MassTag, 'all'))
				$query = trim($query . ' submit:' . Auth::getCurrentUser()->name);
		}

		$ret = Api::run(
			new ListPostsJob(),
			[
				ListPostsJob::PAGE_NUMBER => $page,
				ListPostsJob::QUERY => $query
			]);

		$context->transport->posts = $ret->posts;
		$context->transport->paginator = new StdClass;
		$context->transport->paginator->page = $ret->page;
		$context->transport->paginator->pageCount = $ret->pageCount;
		$context->transport->paginator->entityCount = $ret->postCount;
		$context->transport->paginator->entities = $ret->posts;
	}

	public function favoritesView($page = 1)
	{
		$this->listView('favmin:1', $page);
	}

	public function upvotedView($page = 1)
	{
		$this->listView('scoremin:1', $page);
	}

	public function randomView($page = 1)
	{
		$this->listView('order:random', $page);
	}

	public function toggleTagAction($id, $tag, $enable)
	{
		Access::assert(
			Privilege::MassTag,
			Access::getIdentity(PostModel::findById($id)->getUploader()));

		Api::run(
			new TogglePostTagJob(),
			[
				TogglePostTagJob::POST_ID => $id,
				TogglePostTagJob::TAG_NAME => $tag,
				TogglePostTagJob::STATE => $enable,
			]);
	}

	public function uploadView()
	{
	}

	public function uploadAction()
	{
		$jobArgs =
		[
			AddPostJob::ANONYMOUS => InputHelper::get('anonymous'),
			EditPostSafetyJob::SAFETY => InputHelper::get('safety'),
			EditPostTagsJob::TAG_NAMES => InputHelper::get('tags'),
			EditPostSourceJob::SOURCE => InputHelper::get('source'),
		];

		if (!empty(InputHelper::get('url')))
		{
			$jobArgs[EditPostUrlJob::POST_CONTENT_URL] = InputHelper::get('url');
		}
		elseif (!empty($_FILES['file']['name']))
		{
			$file = $_FILES['file'];
			TransferHelper::handleUploadErrors($file);

			$jobArgs[EditPostContentJob::POST_CONTENT] = Api::serializeFile(
				$file['tmp_name'],
				$file['name']);
		}

		Api::run(new AddPostJob(), $jobArgs);
	}

	public function editView($id)
	{
		$post = PostModel::findByIdOrName($id);
		$context = getContext()->transport->post = $post;
	}

	public function editAction($id)
	{
		$post = PostModel::findByIdOrName($id);

		$editToken = InputHelper::get('edit-token');
		if ($editToken != $post->getEditToken())
			throw new SimpleException('This post was already edited by someone else in the meantime');

		$jobArgs =
		[
			EditPostJob::POST_ID => $id,
			EditPostSafetyJob::SAFETY => InputHelper::get('safety'),
			EditPostTagsJob::TAG_NAMES => InputHelper::get('tags'),
			EditPostSourceJob::SOURCE => InputHelper::get('source'),
			EditPostRelationsJob::RELATED_POST_IDS => InputHelper::get('relations'),
		];

		if (!empty(InputHelper::get('url')))
		{
			$jobArgs[EditPostUrlJob::POST_CONTENT_URL] = InputHelper::get('url');
		}
		elseif (!empty($_FILES['file']['name']))
		{
			$file = $_FILES['file'];
			TransferHelper::handleUploadErrors($file);

			$jobArgs[EditPostContentJob::POST_CONTENT] = Api::serializeFile(
				$file['tmp_name'],
				$file['name']);
		}

		if (!empty($_FILES['thumb']['name']))
		{
			$file = $_FILES['thumb'];
			TransferHelper::handleUploadErrors($file);

			$jobArgs[EditPostThumbJob::THUMB_CONTENT] = Api::serializeFile(
				$file['tmp_name'],
				$file['name']);
		}

		Api::run(new EditPostJob(), $jobArgs);
		TagModel::removeUnused();
	}

	public function flagAction($id)
	{
		Api::run(new FlagPostJob(), [FlagPostJob::POST_ID => $id]);
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
	}

	public function deleteAction($id)
	{
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::DeletePost, Access::getIdentity($post->getUploader()));

		if (!InputHelper::get('submit'))
			return;

		PostModel::remove($post);

		LogHelper::log('{user} deleted {post}', ['post' => TextHelper::reprPost($id)]);
	}

	public function addFavoriteAction($id)
	{
		$context = getContext();
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::FavoritePost, Access::getIdentity($post->getUploader()));
		Access::assertAuthentication();

		if (!InputHelper::get('submit'))
			return;

		UserModel::updateUserScore(Auth::getCurrentUser(), $post, 1);
		UserModel::addToUserFavorites(Auth::getCurrentUser(), $post);
	}

	public function removeFavoriteAction($id)
	{
		$context = getContext();
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::FavoritePost, Access::getIdentity($post->getUploader()));
		Access::assertAuthentication();

		if (!InputHelper::get('submit'))
			return;

		UserModel::removeFromUserFavorites(Auth::getCurrentUser(), $post);
	}

	public function scoreAction($id, $score)
	{
		$context = getContext();
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::ScorePost, Access::getIdentity($post->getUploader()));
		Access::assertAuthentication();

		if (!InputHelper::get('submit'))
			return;

		UserModel::updateUserScore(Auth::getCurrentUser(), $post, $score);
	}

	public function featureAction($id)
	{
		$context = getContext();
		$post = PostModel::findByIdOrName($id);
		Access::assert(Privilege::FeaturePost, Access::getIdentity($post->getUploader()));
		PropertyModel::set(PropertyModel::FeaturedPostId, $post->id);
		PropertyModel::set(PropertyModel::FeaturedPostDate, time());
		PropertyModel::set(PropertyModel::FeaturedPostUserName, Auth::getCurrentUser()->name);
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
}
