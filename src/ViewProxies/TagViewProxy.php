<?php
namespace Szurubooru\ViewProxies;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TagHistoryService;

class TagViewProxy extends AbstractViewProxy
{
	const FETCH_IMPLICATIONS = 'fetchImplications';
	const FETCH_SUGGESTIONS = 'fetchSuggestions';
	const FETCH_HISTORY = 'fetchHistory';

	private $privilegeService;
	private $tagHistoryService;
	private $snapshotViewProxy;

	public function __construct(
		PrivilegeService $privilegeService,
		TagHistoryService $tagHistoryService,
		SnapshotViewProxy $snapshotViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->tagHistoryService = $tagHistoryService;
		$this->snapshotViewProxy = $snapshotViewProxy;
	}

	public function fromEntity($tag, $config = [])
	{
		$result = new \StdClass;
		if ($tag)
		{
			$result->name = $tag->getName();
			$result->usages = $tag->getUsages();
			$result->banned = $tag->isBanned();
			$result->category = $tag->getCategory();

			if (!empty($config[self::FETCH_IMPLICATIONS]))
				$result->implications = $this->fromArray($tag->getImpliedTags());

			if (!empty($config[self::FETCH_SUGGESTIONS]))
				$result->suggestions = $this->fromArray($tag->getSuggestedTags());

			if (!empty($config[self::FETCH_HISTORY]))
			{
				$result->history = $this->privilegeService->hasPrivilege(Privilege::VIEW_HISTORY)
					? $this->snapshotViewProxy->fromArray($this->tagHistoryService->getTagHistory($tag))
					: [];
			}
		}
		return $result;
	}
}
