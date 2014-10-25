<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\GlobalParamDao;
use Szurubooru\Entities\GlobalParam;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Helpers\EnumHelper;

class PostSnapshotProvider
{
	private $globalParamDao;

	public function __construct(GlobalParamDao $globalParamDao)
	{
		$this->globalParamDao = $globalParamDao;
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

			'tags' =>
				array_values(array_map(
					function ($tag)
					{
						return $tag->getName();
					},
					$post->getTags())),

			'relations' =>
				array_values(array_map(
					function ($post)
					{
						return $post->getId();
					},
					$post->getRelatedPosts())),

			'flags' => $flags,
		];

		sort($data['tags']);
		sort($data['relations']);

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
