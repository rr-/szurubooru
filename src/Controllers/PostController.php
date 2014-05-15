<?php
class PostController
{
	public function listView($query = null, $page = 1, $source = 'posts', $additionalInfo = null)
	{
		$context = Core::getContext();
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
			exit;
		}

		$query = trim($query);
		$context->transport->searchQuery = $query;
		$context->transport->lastSearchQuery = $query;
		if ($source == 'mass-tag')
		{
			Access::assert(new Privilege(Privilege::MassTag));
			$context->massTagTag = $additionalInfo;
			$context->massTagQuery = $query;

			if (!Access::check(new Privilege(Privilege::MassTag, 'all')))
				$query = trim($query . ' submit:' . Auth::getCurrentUser()->getName());
		}

		$ret = Api::run(
			new ListPostsJob(),
			[
				JobArgs::ARG_PAGE_NUMBER => $page,
				JobArgs::ARG_QUERY => $query
			]);

		$context->transport->posts = $ret->entities;
		$context->transport->paginator = $ret;
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
		Access::assert(new Privilege(
			Privilege::MassTag,
			Access::getIdentity(PostModel::getById($id)->getUploader())));

		Api::run(
			new TogglePostTagJob(),
			[
				JobArgs::ARG_POST_ID => $id,
				JobArgs::ARG_TAG_NAME => $tag,
				JobArgs::ARG_NEW_STATE => $enable,
			]);
	}

	public function uploadView()
	{
	}

	public function uploadAction()
	{
		$jobArgs =
		[
			JobArgs::ARG_ANONYMOUS => InputHelper::get('anonymous'),
			JobArgs::ARG_NEW_SAFETY => InputHelper::get('safety'),
			JobArgs::ARG_NEW_TAG_NAMES => $this->splitTags(InputHelper::get('tags')),
			JobArgs::ARG_NEW_SOURCE => InputHelper::get('source'),
		];

		if (!empty(InputHelper::get('url')))
		{
			$jobArgs[JobArgs::ARG_NEW_POST_CONTENT_URL] = InputHelper::get('url');
		}
		elseif (!empty($_FILES['file']['name']))
		{
			$file = $_FILES['file'];
			TransferHelper::handleUploadErrors($file);

			$jobArgs[JobArgs::ARG_NEW_POST_CONTENT] = new ApiFileInput(
				$file['tmp_name'],
				$file['name']);
		}

		Api::run(new AddPostJob(), $jobArgs);
	}

	public function editView($id)
	{
		$post = Api::run(new GetPostJob(), [
			JobArgs::ARG_POST_ID => $id]);

		$context = Core::getContext()->transport->post = $post;
	}

	public function editAction($id)
	{
		$post = PostModel::getByIdOrName($id);

		$editToken = InputHelper::get('edit-token');
		if ($editToken != $post->getEditToken())
			throw new SimpleException('This post was already edited by someone else in the meantime');

		$jobArgs =
		[
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_SAFETY => InputHelper::get('safety'),
			JobArgs::ARG_NEW_TAG_NAMES => $this->splitTags(InputHelper::get('tags')),
			JobArgs::ARG_NEW_SOURCE => InputHelper::get('source'),
			JobArgs::ARG_NEW_RELATED_POST_IDS => $this->splitPostIds(InputHelper::get('relations')),
		];

		if (!empty(InputHelper::get('url')))
		{
			$jobArgs[JobArgs::ARG_NEW_POST_CONTENT_URL] = InputHelper::get('url');
		}
		elseif (!empty($_FILES['file']['name']))
		{
			$file = $_FILES['file'];
			TransferHelper::handleUploadErrors($file);

			$jobArgs[JobArgs::ARG_NEW_POST_CONTENT] = new ApiFileInput(
				$file['tmp_name'],
				$file['name']);
		}

		if (!empty($_FILES['thumb']['name']))
		{
			$file = $_FILES['thumb'];
			TransferHelper::handleUploadErrors($file);

			$jobArgs[JobArgs::ARG_NEW_THUMB_CONTENT] = new ApiFileInput(
				$file['tmp_name'],
				$file['name']);
		}

		Api::run(new EditPostJob(), $jobArgs);
	}

	public function flagAction($id)
	{
		Api::run(new FlagPostJob(), [JobArgs::ARG_POST_ID => $id]);
	}

	public function hideAction($id)
	{
		Api::run(new TogglePostVisibilityJob(), [
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_STATE => false]);
	}

	public function unhideAction($id)
	{
		Api::run(new TogglePostVisibilityJob(), [
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_STATE => true]);
	}

	public function deleteAction($id)
	{
		Api::run(new DeletePostJob(), [
			JobArgs::ARG_POST_ID => $id]);
	}

	public function addFavoriteAction($id)
	{
		Api::run(new TogglePostFavoriteJob(), [
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_STATE => true]);
	}

	public function removeFavoriteAction($id)
	{
		Api::run(new TogglePostFavoriteJob(), [
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_STATE => false]);
	}

	public function scoreAction($id, $score)
	{
		Api::run(new ScorePostJob(), [
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_POST_SCORE => $score]);
	}

	public function featureAction($id)
	{
		Api::run(new FeaturePostJob(), [
			JobArgs::ARG_POST_ID => $id]);
	}

	public function genericView($id)
	{
		$context = Core::getContext();
		$context->viewName = 'post-view';

		$post = Api::run(new GetPostJob(), [
			JobArgs::ARG_POST_ID => $id]);

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

		//todo:
		//move these to PostEntity when implementing ApiController
		$isUserFavorite = Auth::getCurrentUser()->hasFavorited($post);
		$userScore = Auth::getCurrentUser()->getScore($post);
		$flagged = in_array(TextHelper::reprPost($post), SessionHelper::get('flagged', []));

		$context->isUserFavorite = $isUserFavorite;
		$context->userScore = $userScore;
		$context->flagged = $flagged;
		$context->transport->post = $post;
		$context->transport->prevPostId = $prevPostId ? $prevPostId : null;
		$context->transport->nextPostId = $nextPostId ? $nextPostId : null;
	}

	public function fileView($name)
	{
		$ret = Api::run(new GetPostContentJob(), [JobArgs::ARG_POST_NAME => $name]);

		$context = Core::getContext();
		$context->transport->cacheDaysToLive = 14;
		$context->transport->customFileName = $ret->fileName;
		$context->transport->mimeType = $ret->mimeType;
		$context->transport->fileHash = 'post' . md5(substr($ret->fileContent, 0, 4096));
		$context->transport->fileContent = $ret->fileContent;
		$context->transport->lastModified = $ret->lastModified;
		$context->layoutName = 'layout-file';
	}

	public function thumbView($name)
	{
		$ret = Api::run(new GetPostThumbJob(), [JobArgs::ARG_POST_NAME => $name]);

		$context = Core::getContext();
		$context->transport->cacheDaysToLive = 365;
		$context->transport->customFileName = $ret->fileName;
		$context->transport->mimeType = 'image/jpeg';
		$context->transport->fileHash = 'thumb' . md5(substr($ret->fileContent, 0, 4096));
		$context->transport->fileContent = $ret->fileContent;
		$context->transport->lastModified = $ret->lastModified;
		$context->layoutName = 'layout-file';
	}

	protected function splitPostIds($string)
	{
		$ids = preg_split('/\D/', trim($string));
		$ids = array_filter($ids, function($x) { return $x != ''; });
		$ids = array_map('intval', $ids);
		$ids = array_unique($ids);
		return $ids;
	}

	protected function splitTags($string)
	{
		$tags = preg_split('/[,;\s]+/', trim($string));
		$tags = array_filter($tags, function($x) { return $x != ''; });
		$tags = array_unique($tags);
		return $tags;
	}
}
