<?php
class TagController
{
	public function listView($filter = 'order:alpha,asc', $page = 1)
	{
		$ret = Api::run(
			new ListTagsJob(),
			[
				ListTagsJob::PAGE_NUMBER => $page,
				ListTagsJob::QUERY => $filter,
			]);

		$context = getContext();
		$context->viewName = 'tag-list-wrapper';
		$context->highestUsage = TagSearchService::getMostUsedTag()['post_count'];
		$context->filter = $filter;
		$context->transport->tags = $ret->entities;
		$context->transport->paginator = $ret;
	}

	public function autoCompleteAction()
	{
		$context = getContext();
		Access::assert(Privilege::ListTags);

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
		Access::assert(Privilege::ListTags);

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

		Access::assert(Privilege::MergeTags);
		if (!InputHelper::get('submit'))
			return;

		TagModel::removeUnused();

		$suppliedSourceTag = InputHelper::get('source-tag');
		$suppliedSourceTag = TagModel::validateTag($suppliedSourceTag);

		$suppliedTargetTag = InputHelper::get('target-tag');
		$suppliedTargetTag = TagModel::validateTag($suppliedTargetTag);

		TagModel::merge($suppliedSourceTag, $suppliedTargetTag);

		LogHelper::log('{user} merged {source} with {target}', [
			'source' => TextHelper::reprTag($suppliedSourceTag),
			'target' => TextHelper::reprTag($suppliedTargetTag)]);

		Messenger::message('Tags merged successfully.');
	}

	public function renameAction()
	{
		$context = getContext();
		$context->viewName = 'tag-list-wrapper';
		$context->handleExceptions = true;

		Access::assert(Privilege::MergeTags);
		if (!InputHelper::get('submit'))
			return;

		TagModel::removeUnused();

		$suppliedSourceTag = InputHelper::get('source-tag');
		$suppliedSourceTag = TagModel::validateTag($suppliedSourceTag);

		$suppliedTargetTag = InputHelper::get('target-tag');
		$suppliedTargetTag = TagModel::validateTag($suppliedTargetTag);

		TagModel::rename($suppliedSourceTag, $suppliedTargetTag);

		LogHelper::log('{user} renamed {source} to {target}', [
			'source' => TextHelper::reprTag($suppliedSourceTag),
			'target' => TextHelper::reprTag($suppliedTargetTag)]);

		Messenger::message('Tag renamed successfully.');
	}

	public function massTagRedirectAction()
	{
		$context = getContext();
		$context->viewName = 'tag-list-wrapper';

		Access::assert(Privilege::MassTag);
		if (!InputHelper::get('submit'))
			return;

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
		\Chibi\Util\Url::forward(\Chibi\Router::linkTo(['PostController', 'listView'], $params));
		exit;
	}
}
