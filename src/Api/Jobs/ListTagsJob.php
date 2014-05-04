<?php
class ListTagsJob extends AbstractJob
{
	public function execute()
	{
		$page = $this->getArgument(self::PAGE_NUMBER);
		$query = $this->getArgument(self::QUERY);

		$page = max(1, intval($page));
		$tagsPerPage = intval(getConfig()->browsing->tagsPerPage);

		$tags = TagSearchService::getEntitiesRows($query, $tagsPerPage, $page);
		$tagCount = TagSearchService::getEntityCount($query);
		$pageCount = ceil($tagCount / $tagsPerPage);
		$page = min($pageCount, $page);

		$ret = new StdClass;
		$ret->tags = $tags;
		$ret->tagCount = $tagCount;
		$ret->page = $page;
		$ret->pageCount = $pageCount;
		return $ret;
	}

	public function requiresPrivilege()
	{
		return Privilege::ListTags;
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
