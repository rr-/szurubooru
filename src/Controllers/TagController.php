<?php
class TagController extends AbstractController
{
	public function listView($filter = 'order:alpha,asc', $page = 1)
	{
		$ret = Api::run(
			new ListTagsJob(),
			[
				JobArgs::ARG_PAGE_NUMBER => $page,
				JobArgs::ARG_QUERY => $filter,
			]);

		$context = Core::getContext();
		$context->highestUsage = TagSearchService::getMostUsedTag()->getPostCount();
		$context->filter = $filter;
		$context->transport->tags = $ret->entities;
		$context->transport->paginator = $ret;
		$this->renderViewWithSource('tag-list-wrapper', 'list');
	}

	public function autoCompleteView()
	{
		$filter = InputHelper::get('search');
		$filter .= ' order:popularity,desc';

		$job = new ListTagsJob();
		$job->getPager()->setPageSize(15);
		$ret = Api::run(
			$job,
			[
				JobArgs::ARG_QUERY => $filter,
				JobArgs::ARG_PAGE_NUMBER => 1,
			]);

		$context = Core::getContext();
		$context->transport->tags =
			array_values(array_map(
				function($tag)
				{
					return [
						'name' => $tag->getName(),
						'count' => $tag->getPostCount(),
					];
				}, $ret->entities));

		$this->renderAjax();
	}

	public function relatedView()
	{
		$otherTags = (array) InputHelper::get('context');
		$tag = InputHelper::get('tag');

		$ret = Api::run(
			(new ListRelatedTagsJob),
			[
				JobArgs::ARG_TAG_NAME => $tag,
				JobArgs::ARG_TAG_NAMES => $otherTags,
				JobArgs::ARG_PAGE_NUMBER => 1
			]);

		$context = Core::getContext();
		$context->transport->tags =
			array_values(array_map(
				function($tag)
				{
					return [
						'name' => $tag->getName(),
						'count' => $tag->getPostCount(),
					];
				}, $ret->entities));

		$this->renderAjax();
	}

	public function mergeView()
	{
		$this->renderViewWithSource('tag-list-wrapper', 'merge');
	}

	public function mergeAction()
	{
		try
		{
			Api::run(
				new MergeTagsJob(),
				[
					JobArgs::ARG_SOURCE_TAG_NAME => InputHelper::get('source-tag'),
					JobArgs::ARG_TARGET_TAG_NAME => InputHelper::get('target-tag'),
				]);

			Messenger::success('Tags merged successfully.');
		}
		catch (SimpleException $e)
		{
			Messenger::fail($e->getMessage());
		}

		$this->renderViewWithSource('tag-list-wrapper', 'merge');
	}

	public function renameView()
	{
		$this->renderViewWithSource('tag-list-wrapper', 'rename');
	}

	public function renameAction()
	{
		try
		{
			Api::run(
				new RenameTagsJob(),
				[
					JobArgs::ARG_SOURCE_TAG_NAME => InputHelper::get('source-tag'),
					JobArgs::ARG_TARGET_TAG_NAME => InputHelper::get('target-tag'),
				]);

			Messenger::success('Tag renamed successfully.');
		}
		catch (Exception $e)
		{
			Messenger::fail($e->getMessage());
		}

		$this->renderViewWithSource('tag-list-wrapper', 'rename');
	}

	public function massTagRedirectView()
	{
		Access::assert(new Privilege(Privilege::MassTag));
		$this->renderViewWithSource('tag-list-wrapper', 'mass-tag');
	}

	public function massTagRedirectAction()
	{
		Access::assert(new Privilege(Privilege::MassTag));
		$suppliedOldPage = intval(InputHelper::get('old-page'));
		$suppliedOldQuery = InputHelper::get('old-query');
		$suppliedQuery = InputHelper::get('query');
		$suppliedTag = InputHelper::get('tag');

		$params =
		[
			'source' => 'mass-tag',
			'query' => trim($suppliedQuery ?: ''),
			'additionalInfo' => $suppliedTag ? $suppliedTag : '',
		];

		if ($suppliedOldPage != 0 and $suppliedOldQuery == $suppliedQuery)
			$params['page'] = $suppliedOldPage;
		else
			$params['page'] = 1;

		$url = \Chibi\Router::linkTo(['PostController', 'listView'], $params);
		$this->redirect($url);
	}


	private function renderViewWithSource($viewName, $source)
	{
		$context = Core::getContext();
		$context->source = $source;
		$this->renderView($viewName);
	}
}
