<?php
namespace Szurubooru\Controllers\ViewProxies;

class PostViewProxy extends AbstractViewProxy
{
	const FETCH_USER = 'fetchUser';
	const FETCH_TAGS = 'fetchTags';
	const FETCH_RELATIONS = 'fetchRelations';
	const FETCH_HISTORY = 'fetchHistory';
	const FETCH_OWN_SCORE = 'fetchOwnScore';
	const FETCH_FAVORITES = 'fetchFavorites';

	private $privilegeService;
	private $authService;
	private $historyService;
	private $favoritesService;
	private $postScoreService;
	private $tagViewProxy;
	private $userViewProxy;
	private $snapshotViewProxy;

	public function __construct(
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\HistoryService $historyService,
		\Szurubooru\Services\FavoritesService $favoritesService,
		\Szurubooru\Services\PostScoreService $postScoreService,
		TagViewProxy $tagViewProxy,
		UserViewProxy $userViewProxy,
		SnapshotViewProxy $snapshotViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->authService = $authService;
		$this->historyService = $historyService;
		$this->favoritesService = $favoritesService;
		$this->postScoreService = $postScoreService;
		$this->tagViewProxy = $tagViewProxy;
		$this->userViewProxy = $userViewProxy;
		$this->snapshotViewProxy = $snapshotViewProxy;
	}

	public function fromEntity($post, $config = [])
	{
		$result = new \StdClass;
		if (!$post)
			return $result;

		$result->id = $post->getId();
		$result->idMarkdown = $post->getIdMarkdown();
		$result->name = $post->getName();
		$result->uploadTime = $post->getUploadTime();
		$result->lastEditTime = $post->getLastEditTime();
		$result->safety = \Szurubooru\Helpers\EnumHelper::postSafetyToString($post->getSafety());
		$result->contentType = \Szurubooru\Helpers\EnumHelper::postTypeToString($post->getContentType());
		$result->contentChecksum = $post->getContentChecksum();
		$result->contentMimeType = $post->getContentMimeType();
		$result->contentExtension = \Szurubooru\Helpers\MimeHelper::getExtension($post->getContentMimeType());
		$result->source = $post->getSource();
		$result->imageWidth = $post->getImageWidth();
		$result->imageHeight = $post->getImageHeight();
		$result->featureCount = $post->getFeatureCount();
		$result->lastFeatureTime = $post->getLastFeatureTime();
		$result->originalFileSize = $post->getOriginalFileSize();
		$result->favoriteCount = $post->getFavoriteCount();
		$result->score = $post->getScore();
		$result->commentCount = $post->getCommentCount();

		if (!empty($config[self::FETCH_TAGS]))
			$result->tags = $this->tagViewProxy->fromArray($post->getTags());

		if (!empty($config[self::FETCH_USER]))
			$result->user = $this->userViewProxy->fromEntity($post->getUser());

		if (!empty($config[self::FETCH_RELATIONS]))
			$result->relations = $this->fromArray($post->getRelatedPosts());

		if (!empty($config[self::FETCH_HISTORY]))
		{
			if ($this->privilegeService->hasPrivilege(\Szurubooru\Privilege::VIEW_HISTORY))
				$result->history = $this->snapshotViewProxy->fromArray($this->historyService->getPostHistory($post));
			else
				$result->history = [];
		}

		if (!empty($config[self::FETCH_OWN_SCORE]) and $this->authService->isLoggedIn())
			$result->ownScore = $this->postScoreService->getScoreValue($this->authService->getLoggedInUser(), $post);

		if (!empty($config[self::FETCH_FAVORITES]))
			$result->favorites = $this->userViewProxy->fromArray($this->favoritesService->getFavoriteUsers($post));


		return $result;
	}
}
