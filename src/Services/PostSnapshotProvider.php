<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\GlobalParamDao;
use Szurubooru\Dao\PostNoteDao;
use Szurubooru\Entities\GlobalParam;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\PostNote;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Entities\Tag;
use Szurubooru\Helpers\EnumHelper;

class PostSnapshotProvider
{
	private $globalParamDao;
	private $postNoteDao;

	public function __construct(
		GlobalParamDao $globalParamDao,
		PostNoteDao $postNoteDao)
	{
		$this->globalParamDao = $globalParamDao;
		$this->postNoteDao = $postNoteDao;
	}

	public function getPostChangeSnapshot(Post $post)
	{
		static $featuredPostParam = null;
		if ($featuredPostParam === null)
			$featuredPostParam = $this->globalParamDao->findByKey(GlobalParam::KEY_FEATURED_POST);
		$isFeatured = ($featuredPostParam and intval($featuredPostParam->getValue()) === $post->getId());

		$flags = [];
		if ($post->getFlags() & Post::FLAG_LOOP)
			$flags[] = 'loop';

		$data =
		[
			'source' => $post->getSource(),
			'safety' => EnumHelper::postSafetyToString($post->getSafety()),
			'contentChecksum' => $post->getContentChecksum(),
			'featured' => $isFeatured,

			'notes' => array_values(array_map(function (PostNote $note)
				{
					return [
						'x' => intval($note->getLeft()),
						'y' => intval($note->getTop()),
						'w' => intval($note->getWidth()),
						'h' => intval($note->getHeight()),
						'text' => trim($note->getText()),
					];
				},
				$this->postNoteDao->findByPostId($post->getId()))),

			'tags' =>
				array_values(array_map(
					function (Tag $tag)
					{
						return $tag->getName();
					},
					$post->getTags())),

			'relations' =>
				array_values(array_map(
					function (Post $post)
					{
						return $post->getId();
					},
					$post->getRelatedPosts())),

			'flags' => $flags,
		];

		sort($data['tags']);
		sort($data['relations']);
		usort($data['notes'], function ($note1, $note2) { return $note1['x'] - $note2['x']; });

		$snapshot = $this->getPostSnapshot($post);
		$snapshot->setOperation(Snapshot::OPERATION_CHANGE);
		$snapshot->setData($data);
		return $snapshot;
	}

	public function getPostDeleteSnapshot(Post $post)
	{
		$snapshot = $this->getPostSnapshot($post);
		$snapshot->setData([]);
		$snapshot->setOperation(Snapshot::OPERATION_DELETE);
		return $snapshot;
	}

	private function getPostSnapshot(Post $post)
	{
		$snapshot = new Snapshot();
		$snapshot->setType(Snapshot::TYPE_POST);
		$snapshot->setPrimaryKey($post->getId());
		return $snapshot;
	}
}
