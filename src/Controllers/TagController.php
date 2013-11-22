<?php
class TagController
{
	/**
	* @route /tags
	* @route /tags/{filter}
	* @validate filter [a-zA-Z\32:,_-]+
	*/
	public function listAction($filter = null)
	{
		$this->context->stylesheets []= 'tag-list.css';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->subTitle = 'tags';
		$this->context->viewName = 'tag-list-wrapper';

		PrivilegesHelper::confirmWithException(Privilege::ListTags);
		$suppliedFilter = $filter ?: InputHelper::get('filter') ?: 'order:alpha,asc';

		$tags = Model_Tag::getEntitiesRows($suppliedFilter, null, null);
		$this->context->filter = $suppliedFilter;
		$this->context->transport->tags = $tags;

		if ($this->context->json)
		{
			$this->context->transport->tags = array_values(array_map(function($tag) {
				return ['name' => $tag['name'], 'count' => $tag['post_count']];
			}, $this->context->transport->tags));
		}
	}

	/**
	* @route /tag/merge
	*/
	public function mergeAction()
	{
		$this->context->stylesheets []= 'tag-list.css';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->subTitle = 'tags';
		$this->context->viewName = 'tag-list-wrapper';

		PrivilegesHelper::confirmWithException(Privilege::MergeTags);
		if (InputHelper::get('submit'))
		{
			Model_Tag::removeUnused();

			$suppliedSourceTag = InputHelper::get('source-tag');
			$suppliedSourceTag = Model_Tag::validateTag($suppliedSourceTag);
			$sourceTag = Model_Tag::locate($suppliedSourceTag);

			$suppliedTargetTag = InputHelper::get('target-tag');
			$suppliedTargetTag = Model_Tag::validateTag($suppliedTargetTag);
			$targetTag = Model_Tag::locate($suppliedTargetTag);

			if ($sourceTag->id == $targetTag->id)
				throw new SimpleException('Source and target tag are the same');

			R::preload($sourceTag, 'post');

			foreach ($sourceTag->sharedPost as $post)
			{
				foreach ($post->sharedTag as $key => $postTag)
					if ($postTag->id == $sourceTag->id)
						unset($post->sharedTag[$key]);
				$post->sharedTag []= $targetTag;
				Model_Post::save($post);
			}
			Model_Tag::remove($sourceTag);

			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('tag', 'list'));
			LogHelper::log('{user} merged {source} with {target}', ['source' => TextHelper::reprTag($suppliedSourceTag), 'target' => TextHelper::reprTag($suppliedTargetTag)]);
			StatusHelper::success();
		}
	}

	/**
	* @route /tag/rename
	*/
	public function renameAction()
	{
		$this->context->stylesheets []= 'tag-list.css';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->subTitle = 'tags';
		$this->context->viewName = 'tag-list-wrapper';

		PrivilegesHelper::confirmWithException(Privilege::MergeTags);
		if (InputHelper::get('submit'))
		{
			Model_Tag::removeUnused();

			$suppliedSourceTag = InputHelper::get('source-tag');
			$suppliedSourceTag = Model_Tag::validateTag($suppliedSourceTag);
			$sourceTag = Model_Tag::locate($suppliedSourceTag);

			$suppliedTargetTag = InputHelper::get('target-tag');
			$suppliedTargetTag = Model_Tag::validateTag($suppliedTargetTag);
			$targetTag = Model_Tag::locate($suppliedTargetTag, false);

			if ($targetTag and $targetTag->id != $sourceTag->id)
				throw new SimpleException('Target tag already exists');

			$sourceTag->name = $suppliedTargetTag;
			Model_Tag::save($sourceTag);

			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('tag', 'list'));
			LogHelper::log('{user} renamed {source} to {target}', ['source' => TextHelper::reprTag($suppliedSourceTag), 'target' => TextHelper::reprTag($suppliedTargetTag)]);
			StatusHelper::success();
		}
	}

	/**
	* @route /mass-tag-redirect
	*/
	public function massTagRedirectAction()
	{
		$this->context->stylesheets []= 'tag-list.css';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->subTitle = 'tags';
		$this->context->viewName = 'tag-list-wrapper';

		PrivilegesHelper::confirmWithException(Privilege::MassTag);
		if (InputHelper::get('submit'))
		{
			$suppliedQuery = InputHelper::get('query');
			if (!$suppliedQuery)
				$suppliedQuery = ' ';
			$suppliedTag = InputHelper::get('tag');
			if (!empty($suppliedTag))
				$suppliedTag = Model_Tag::validateTag($suppliedTag);
			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('post', 'list', ['source' => 'mass-tag', 'query' => $suppliedQuery, 'additionalInfo' => $suppliedTag]));
		}
	}
}
