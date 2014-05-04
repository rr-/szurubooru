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
			exit;
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

			$jobArgs[EditPostContentJob::POST_CONTENT] = new ApiFileInput(
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

			$jobArgs[EditPostContentJob::POST_CONTENT] = new ApiFileInput(
				$file['tmp_name'],
				$file['name']);
		}

		if (!empty($_FILES['thumb']['name']))
		{
			$file = $_FILES['thumb'];
			TransferHelper::handleUploadErrors($file);

			$jobArgs[EditPostThumbJob::THUMB_CONTENT] = new ApiFileInput(
				$file['tmp_name'],
				$file['name']);
		}

		Api::run(new EditPostJob(), $jobArgs);
	}

	public function flagAction($id)
	{
		Api::run(new FlagPostJob(), [FlagPostJob::POST_ID => $id]);
	}

	public function hideAction($id)
	{
		Api::run(new TogglePostVisibilityJob(), [
			TogglePostVisibilityJob::POST_ID => $id,
			TogglePostVisibilityJob::STATE => false]);
	}

	public function unhideAction($id)
	{
		Api::run(new TogglePostVisibilityJob(), [
			TogglePostVisibilityJob::POST_ID => $id,
			TogglePostVisibilityJob::STATE => true]);
	}

	public function deleteAction($id)
	{
		Api::run(new DeletePostJob(), [
			DeletePostJob::POST_ID => $id]);
	}

	public function addFavoriteAction($id)
	{
		Api::run(new TogglePostFavoriteJob(), [
			TogglePostFavoriteJob::POST_ID => $id,
			TogglePostFavoriteJob::STATE => true]);
	}

	public function removeFavoriteAction($id)
	{
		Api::run(new TogglePostFavoriteJob(), [
			TogglePostFavoriteJob::POST_ID => $id,
			TogglePostFavoriteJob::STATE => false]);
	}

	public function scoreAction($id, $score)
	{
		Api::run(new ScorePostJob(), [
			ScorePostJob::POST_ID => $id,
			ScorePostJob::SCORE => $score]);
	}

	public function featureAction($id)
	{
		Api::run(new FeaturePostJob(), [
			FeaturePostJob::POST_ID => $id]);
	}

	public function genericView($id)
	{
		$context = getContext();
		$context->viewName = 'post-view';

		$post = Api::run(new GetPostJob(), [
			GetPostJob::POST_ID => $id]);

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

	public function fileView($name)
	{
		$ret = Api::run(new GetPostContentJob(), [GetPostContentJob::POST_NAME => $name]);

		$context = getContext();
		$context->transport->cacheDaysToLive = 14;
		$context->transport->customFileName = $ret->fileName;
		$context->transport->mimeType = $ret->mimeType;
		$context->transport->fileHash = 'post' . md5(substr($ret->fileContent, 0, 4096));
		$context->transport->fileContent = $ret->fileContent;
		$context->transport->lastModified = $ret->lastModified;
		$context->layoutName = 'layout-file';
	}

	public function thumbView($name, $width = null, $height = null)
	{
		$ret = Api::run(new GetPostThumbJob(), [
			GetPostThumbJob::POST_NAME => $name,
			GetPostThumbJob::WIDTH => $width,
			GetPostThumbJob::HEIGHT => $height]);

		$context = getContext();
		$context->transport->cacheDaysToLive = 365;
		$context->transport->customFileName = $ret->fileName;
		$context->transport->mimeType = 'image/jpeg';
		$context->transport->fileHash = 'thumb' . md5(substr($ret->fileContent, 0, 4096));
		$context->transport->fileContent = $ret->fileContent;
		$context->transport->lastModified = $ret->lastModified;
		$context->layoutName = 'layout-file';
	}
}
