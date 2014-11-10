<?php
namespace Szurubooru\Controllers\ViewProxies;
use Szurubooru\Entities\Post;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\Privilege;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\FavoritesService;
use Szurubooru\Services\PostHistoryService;
use Szurubooru\Services\PostNotesService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\ScoreService;

class PostViewProxy extends AbstractViewProxy
{
	const FETCH_USER = 'fetchUser';
	const FETCH_TAGS = 'fetchTags';
	const FETCH_RELATIONS = 'fetchRelations';
	const FETCH_HISTORY = 'fetchHistory';
	const FETCH_OWN_SCORE = 'fetchOwnScore';
	const FETCH_FAVORITES = 'fetchFavorites';
	const FETCH_NOTES = 'fetchNotes';

	private $privilegeService;
	private $authService;
	private $postHistoryService;
	private $favoritesService;
	private $scoreService;
	private $postNotesService;
	private $tagViewProxy;
	private $userViewProxy;
	private $snapshotViewProxy;
	private $postNoteViewProxy;

	public function __construct(
		PrivilegeService $privilegeService,
		AuthService $authService,
		PostHistoryService $postHistoryService,
		FavoritesService $favoritesService,
		ScoreService $scoreService,
		PostNotesService $postNotesService,
		TagViewProxy $tagViewProxy,
		UserViewProxy $userViewProxy,
		SnapshotViewProxy $snapshotViewProxy,
		PostNoteViewProxy $postNoteViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->authService = $authService;
		$this->postHistoryService = $postHistoryService;
		$this->favoritesService = $favoritesService;
		$this->scoreService = $scoreService;
		$this->postNotesService = $postNotesService;
		$this->tagViewProxy = $tagViewProxy;
		$this->userViewProxy = $userViewProxy;
		$this->snapshotViewProxy = $snapshotViewProxy;
		$this->postNoteViewProxy = $postNoteViewProxy;
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
		$result->safety = EnumHelper::postSafetyToString($post->getSafety());
		$result->contentType = EnumHelper::postTypeToString($post->getContentType());
		$result->contentChecksum = $post->getContentChecksum();
		$result->contentMimeType = $post->getContentMimeType();
		$result->contentExtension = MimeHelper::getExtension($post->getContentMimeType());
		$result->source = $post->getSource();
		$result->imageWidth = $post->getImageWidth();
		$result->imageHeight = $post->getImageHeight();
		$result->featureCount = $post->getFeatureCount();
		$result->lastFeatureTime = $post->getLastFeatureTime();
		$result->originalFileSize = $post->getOriginalFileSize();
		$result->favoriteCount = $post->getFavoriteCount();
		$result->score = $post->getScore();
		$result->commentCount = $post->getCommentCount();
		$result->flags = new \StdClass;
		$result->flags->loop = ($post->getFlags() & Post::FLAG_LOOP);

		if (!empty($config[self::FETCH_TAGS]))
		{
			$result->tags = $this->tagViewProxy->fromArray($post->getTags());
			usort($result->tags, function($tag1, $tag2)
				{
					return strcasecmp($tag1->name, $tag2->name);
				});
		}

		if (!empty($config[self::FETCH_USER]))
			$result->user = $this->userViewProxy->fromEntity($post->getUser());

		if (!empty($config[self::FETCH_RELATIONS]))
			$result->relations = $this->fromArray($post->getRelatedPosts());

		if (!empty($config[self::FETCH_HISTORY]))
		{
			if ($this->privilegeService->hasPrivilege(Privilege::VIEW_HISTORY))
				$result->history = $this->snapshotViewProxy->fromArray($this->postHistoryService->getPostHistory($post));
			else
				$result->history = [];
		}

		if (!empty($config[self::FETCH_OWN_SCORE]) && $this->authService->isLoggedIn())
			$result->ownScore = $this->scoreService->getUserScoreValue($this->authService->getLoggedInUser(), $post);

		if (!empty($config[self::FETCH_FAVORITES]))
			$result->favorites = $this->userViewProxy->fromArray($this->favoritesService->getFavoriteUsers($post));

		if (!empty($config[self::FETCH_NOTES]))
			$result->notes = $this->postNoteViewProxy->fromArray($this->postNotesService->getByPost($post));

		return $result;
	}
}
