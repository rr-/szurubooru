<?php
class ListRelatedTagsJob extends ListTagsJob
{
	public function execute()
	{
		$pageSize = $this->getPageSize();
		$page = $this->getArgument(JobArgs::ARG_PAGE_NUMBER);
		$tag = $this->getArgument(JobArgs::ARG_TAG_NAME);
		$otherTags = $this->hasArgument(JobArgs::ARG_TAG_NAMES) ? $this->getArgument(JobArgs::ARG_TAG_NAMES) : [];

		$tags = TagSearchService::getRelatedTags($tag);
		$tagCount = count($tags);
		$tags = array_filter($tags, function($tag) use ($otherTags) { return !in_array($tag->getName(), $otherTags); });
		$tags = array_slice($tags, 0, $pageSize);

		return $this->getPager($tags, $tagCount, $page, $pageSize);
	}

	public function getDefaultPageSize()
	{
		return intval(getConfig()->browsing->tagsRelated);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			parent::getRequiredArguments(),
			Jobargs::ARG_TAG_NAME,
			JobArgs::Optional(JobArgs::ARG_TAG_NAMES));
	}
}
