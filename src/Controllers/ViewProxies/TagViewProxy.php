<?php
namespace Szurubooru\Controllers\ViewProxies;

class TagViewProxy extends AbstractViewProxy
{
	const FETCH_IMPLICATIONS = 'fetchImplications';
	const FETCH_SUGGESTIONS = 'fetchSuggestions';

	public function fromEntity($tag, $config = [])
	{
		$result = new \StdClass;
		if ($tag)
		{
			$result->name = $tag->getName();
			$result->usages = $tag->getUsages();
			$result->banned = $tag->isBanned();

			if (!empty($config[self::FETCH_IMPLICATIONS]))
				$result->implications = $this->fromArray($tag->getImpliedTags());

			if (!empty($config[self::FETCH_SUGGESTIONS]))
				$result->suggestions = $this->fromArray($tag->getSuggestedTags());
		}
		return $result;
	}
}
