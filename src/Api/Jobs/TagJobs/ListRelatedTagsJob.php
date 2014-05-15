<?php
class ListRelatedTagsJob extends AbstractJob implements IPagedJob
{
	protected $pager;

	public function __construct()
	{
		$this->pager = new JobPager($this);
		$this->pager->setPageSize(Core::getConfig()->browsing->tagsRelated);
	}

	public function getPager()
	{
		return $this->pager;
	}

	public function execute()
	{
		$pageSize = $this->pager->getPageSize();
		$page = $this->pager->getPageNumber();
		$tag = $this->getArgument(JobArgs::ARG_TAG_NAME);
		$otherTags = $this->hasArgument(JobArgs::ARG_TAG_NAMES) ? $this->getArgument(JobArgs::ARG_TAG_NAMES) : [];

		$tags = TagSearchService::getRelatedTags($tag);
		$tagCount = count($tags);
		$tags = array_filter($tags, function($tag) use ($otherTags) { return !in_array($tag->getName(), $otherTags); });
		$tags = array_slice($tags, 0, $pageSize);

		return $this->pager->serialize($tags, $tagCount);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->pager->getRequiredArguments(),
			Jobargs::ARG_TAG_NAME,
			JobArgs::Optional(JobArgs::ARG_TAG_NAMES));
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(Privilege::ListTags);
	}
}
