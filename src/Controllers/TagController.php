<?php
class TagController
{
	public function listAction($filter = null, $page = 1)
	{
		$context = getContext();
		$context->viewName = 'tag-list-wrapper';
		PrivilegesHelper::confirmWithException(Privilege::ListTags);

		$suppliedFilter = $filter ?: 'order:alpha,asc';
		$page = max(1, intval($page));
		$tagsPerPage = intval(getConfig()->browsing->tagsPerPage);

		$tags = TagSearchService::getEntitiesRows($suppliedFilter, $tagsPerPage, $page);
		$tagCount = TagSearchService::getEntityCount($suppliedFilter);
		$pageCount = ceil($tagCount / $tagsPerPage);
		$page = min($pageCount, $page);

		$context->filter = $suppliedFilter;
		$context->transport->tags = $tags;

		if ($context->json)
		{
			$context->transport->tags = array_values(array_map(function($tag) {
				return ['name' => $tag['name'], 'count' => $tag['post_count']];
			}, $context->transport->tags));
		}
		else
		{
			$context->highestUsage = TagSearchService::getMostUsedTag()['post_count'];
			$context->transport->paginator = new StdClass;
			$context->transport->paginator->page = $page;
			$context->transport->paginator->pageCount = $pageCount;
			$context->transport->paginator->entityCount = $tagCount;
			$context->transport->paginator->entities = $tags;
		}
	}

	public function autoCompleteAction()
	{
		$context = getContext();
		PrivilegesHelper::confirmWithException(Privilege::ListTags);

		$suppliedSearch = InputHelper::get('search');

		$filter = $suppliedSearch . ' order:popularity,desc';
		$tags = TagSearchService::getEntitiesRows($filter, 15, 1);

		$context->transport->tags =
			array_values(array_map(
				function($tag)
				{
					return [
						'name' => $tag['name'],
						'count' => $tag['post_count']
					];
				}, $tags));
	}

	public function relatedAction()
	{
		$context = getContext();
		PrivilegesHelper::confirmWithException(Privilege::ListTags);

		$suppliedContext = (array) InputHelper::get('context');
		$suppliedTag = InputHelper::get('tag');

		$limit = intval(getConfig()->browsing->tagsRelated);
		$tags = TagSearchService::getRelatedTagRows($suppliedTag, $suppliedContext, $limit);

		$context->transport->tags =
			array_values(array_map(
				function($tag)
				{
					return [
						'name' => $tag['name'],
						'count' => $tag['post_count']
					];
				}, $tags));
	}

	public function mergeAction()
	{
		$context = getContext();
		$context->viewName = 'tag-list-wrapper';
		$context->handleExceptions = true;

		PrivilegesHelper::confirmWithException(Privilege::MergeTags);
		if (InputHelper::get('submit'))
		{
			TagModel::removeUnused();

			$suppliedSourceTag = InputHelper::get('source-tag');
			$suppliedSourceTag = TagModel::validateTag($suppliedSourceTag);

			$suppliedTargetTag = InputHelper::get('target-tag');
			$suppliedTargetTag = TagModel::validateTag($suppliedTargetTag);

			TagModel::merge($suppliedSourceTag, $suppliedTargetTag);

			LogHelper::log('{user} merged {source} with {target}', [
				'source' => TextHelper::reprTag($suppliedSourceTag),
				'target' => TextHelper::reprTag($suppliedTargetTag)]);

			StatusHelper::success('Tags merged successfully.');
		}
	}

	public function renameAction()
	{
		$context = getContext();
		$context->viewName = 'tag-list-wrapper';
		$context->handleExceptions = true;

		PrivilegesHelper::confirmWithException(Privilege::MergeTags);
		if (InputHelper::get('submit'))
		{
			TagModel::removeUnused();

			$suppliedSourceTag = InputHelper::get('source-tag');
			$suppliedSourceTag = TagModel::validateTag($suppliedSourceTag);

			$suppliedTargetTag = InputHelper::get('target-tag');
			$suppliedTargetTag = TagModel::validateTag($suppliedTargetTag);

			TagModel::rename($suppliedSourceTag, $suppliedTargetTag);

			LogHelper::log('{user} renamed {source} to {target}', [
				'source' => TextHelper::reprTag($suppliedSourceTag),
				'target' => TextHelper::reprTag($suppliedTargetTag)]);

			StatusHelper::success('Tag renamed successfully.');
		}
	}

	public function massTagRedirectAction()
	{
		$context = getContext();
		$context->viewName = 'tag-list-wrapper';

		PrivilegesHelper::confirmWithException(Privilege::MassTag);
		if (InputHelper::get('submit'))
		{
			$suppliedOldPage = intval(InputHelper::get('old-page'));
			$suppliedOldQuery = InputHelper::get('old-query');
			$suppliedQuery = InputHelper::get('query');
			$suppliedTag = InputHelper::get('tag');

			$params = [
				'source' => 'mass-tag',
				'query' => $suppliedQuery ?: ' ',
				'additionalInfo' => $suppliedTag ? TagModel::validateTag($suppliedTag) : '',
			];
			if ($suppliedOldPage != 0 and $suppliedOldQuery == $suppliedQuery)
				$params['page'] = $suppliedOldPage;
			\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['PostController', 'listAction'], $params));
		}
	}
}
