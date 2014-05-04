<?php
class ListTagsJob extends AbstractPageJob
{
	public function execute()
	{
		$pageSize = $this->getPageSize();
		$page = $this->getArgument(self::PAGE_NUMBER);
		$query = $this->getArgument(self::QUERY);

		$tags = TagSearchService::getEntities($query, $pageSize, $page);
		$tagCount = TagSearchService::getEntityCount($query);

		return $this->getPager($tags, $tagCount, $page, $pageSize);
	}

	public function getDefaultPageSize()
	{
		return intval(getConfig()->browsing->tagsPerPage);
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::ListTags);
	}
}
