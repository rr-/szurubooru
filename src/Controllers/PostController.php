<?php
class PostController extends AbstractController
{
	public function listView($query = null, $page = 1, $source = 'posts', $additionalInfo = null)
	{
		$context = Core::getContext();
		$context->source = $source;
		$context->additionalInfo = $additionalInfo;

		try
		{
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
		catch (SimpleException $e)
		{
			\Chibi\Util\Headers::setCode(400);
			Messenger::fail($e->getMessage());
		}

		$this->renderView('post-list-wrapper');
	}

	public function listRedirectAction($source = 'posts')
	{
		$context = Core::getContext();

		if ($source == 'mass-tag')
			Access::assert(new Privilege(Privilege::MassTag));

		$oldPage = intval(InputHelper::get('old-page'));
		$oldQuery = trim(InputHelper::get('old-query'));
		$query = trim(InputHelper::get('query'));
		$additionalInfo = trim(InputHelper::get('tag'));

		$context->transport->searchQuery = $query;
		$context->transport->lastSearchQuery = $query;
		if (strpos($query, '/') !== false)
			throw new SimpleException('Search query contains invalid characters');

		$params = [];
		$params['source'] = $source;
		if ($query)
			$params['query'] = $query;
		if ($additionalInfo)
			$params['additionalInfo'] = $additionalInfo;

		if ($oldPage != 0 and $oldQuery == $query)
			$params['page'] = $oldPage;
		else
			$params['page'] = 1;


		$url = \Chibi\Router::linkTo(['PostController', 'listView'], $params);
		$this->redirect($url);
	}

	public function favoritesView($page = 1)
	{
		$this->listView('favmin:1', $page, 'favorites');
	}

	public function upvotedView($page = 1)
	{
		$this->listView('scoremin:1', $page, 'upvoted');
	}

	public function randomView($page = 1)
	{
		$this->listView('order:random', $page, 'random');
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

		if ($this->isAjax())
			$this->renderAjax();
		else
			$this->redirectToLastVisitedUrl();
	}

	public function uploadView()
	{
		$this->renderView('post-upload');
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

		if ($this->isAjax())
			$this->renderAjax();
		else
			$this->redirectToPostList();
	}

	public function editView($id)
	{
		$post = Api::run(new GetPostJob(), [
			JobArgs::ARG_POST_ID => $id]);

		$context = Core::getContext()->transport->post = $post;
		$this->renderView('post-edit');
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

		if (!empty($_FILES['thumbnail']['name']))
		{
			$file = $_FILES['thumbnail'];
			TransferHelper::handleUploadErrors($file);

			$jobArgs[JobArgs::ARG_NEW_THUMBNAIL_CONTENT] = new ApiFileInput(
				$file['tmp_name'],
				$file['name']);
		}

		Api::run(new EditPostJob(), $jobArgs);

		if ($this->isAjax())
			$this->renderAjax();
		else
			$this->redirectToGenericView($id);
	}

	public function flagAction($id)
	{
		Api::run(new FlagPostJob(), [JobArgs::ARG_POST_ID => $id]);

		if ($this->isAjax())
			$this->renderAjax();
		else
			$this->redirectToGenericView($id);
	}

	public function hideAction($id)
	{
		Api::run(new TogglePostVisibilityJob(), [
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_STATE => false]);
		$this->redirectToGenericView($id);
	}

	public function unhideAction($id)
	{
		Api::run(new TogglePostVisibilityJob(), [
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_STATE => true]);
		$this->redirectToGenericView($id);
	}

	public function deleteAction($id)
	{
		Api::run(new DeletePostJob(), [
			JobArgs::ARG_POST_ID => $id]);
		$this->redirectToPostList();
	}

	public function addFavoriteAction($id)
	{
		Api::run(new TogglePostFavoriteJob(), [
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_STATE => true]);
		$this->redirectToGenericView($id);
	}

	public function removeFavoriteAction($id)
	{
		Api::run(new TogglePostFavoriteJob(), [
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_STATE => false]);
		$this->redirectToGenericView($id);
	}

	public function scoreAction($id, $score)
	{
		Api::run(new ScorePostJob(), [
			JobArgs::ARG_POST_ID => $id,
			JobArgs::ARG_NEW_POST_SCORE => $score]);
		$this->redirectToGenericView($id);
	}

	public function featureAction($id)
	{
		Api::run(new FeaturePostJob(), [
			JobArgs::ARG_POST_ID => $id]);
		$this->redirectToGenericView($id);
	}

	public function genericView($id)
	{
		$context = Core::getContext();

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

		$this->renderView('post-view');
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
		$this->renderFile();
	}

	public function thumbnailView($name)
	{
		$ret = Api::run(new GetPostThumbnailJob(), [JobArgs::ARG_POST_NAME => $name]);

		$context = Core::getContext();
		$context->transport->cacheDaysToLive = 365;
		$context->transport->customFileName = $ret->fileName;
		$context->transport->mimeType = 'image/jpeg';
		$context->transport->fileHash = 'thumb' . md5(substr($ret->fileContent, 0, 4096));
		$context->transport->fileContent = $ret->fileContent;
		$context->transport->lastModified = $ret->lastModified;
		$this->renderFile();
	}


	private function splitPostIds($string)
	{
		$ids = preg_split('/\D/', trim($string));
		$ids = array_filter($ids, function($x) { return $x != ''; });
		$ids = array_map('intval', $ids);
		$ids = array_unique($ids);
		return $ids;
	}

	private function splitTags($string)
	{
		$tags = preg_split('/[,;\s]+/', trim($string));
		$tags = array_filter($tags, function($x) { return $x != ''; });
		$tags = array_unique($tags);
		return $tags;
	}

	private function redirectToPostList()
	{
		$this->redirect(\Chibi\Router::linkTo(['PostController', 'listView']));
	}

	private function redirectToGenericView($id)
	{
		$this->redirect(\Chibi\Router::linkTo(
			['PostController', 'genericView'],
			['id' => $id]));
	}
}
