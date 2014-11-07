<?php
namespace Szurubooru\Services;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Entities\Tag;

class TagSnapshotProvider
{
	public function getTagChangeSnapshot(Tag $tag)
	{
		$data =
		[
			'name' => $tag->getName(),
			'banned' => $tag->isBanned(),
			'category' => $tag->getCategory(),

			'implications' => array_values(array_map(function (Tag $impliedTag)
				{
					return $impliedTag->getName();
				}, $tag->getImpliedTags())),

			'suggestions' => array_values(array_map(function (Tag $suggestedTag)
				{
					return $suggestedTag->getName();
				}, $tag->getSuggestedTags())),
		];

		sort($data['implications']);
		sort($data['suggestions']);

		$snapshot = $this->getTagSnapshot($tag);
		$snapshot->setOperation(Snapshot::OPERATION_CHANGE);
		$snapshot->setData($data);
		return $snapshot;
	}

	public function getTagDeleteSnapshot(Tag $tag)
	{
		$snapshot = $this->getTagSnapshot($tag);
		$snapshot->setData([]);
		$snapshot->setOperation(Snapshot::OPERATION_DELETE);
		return $snapshot;
	}

	private function getTagSnapshot(Tag $tag)
	{
		$snapshot = new Snapshot();
		$snapshot->setType(Snapshot::TYPE_TAG);
		$snapshot->setPrimaryKey($tag->getId());
		return $snapshot;
	}
}
