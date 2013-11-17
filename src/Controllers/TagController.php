<?php
class TagController
{
	/**
	* @route /tags
	*/
	public function listAction()
	{
		$this->context->stylesheets []= 'tag-list.css';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->subTitle = 'tags';
		$this->context->viewName = 'tag-list-wrapper';

		PrivilegesHelper::confirmWithException(Privilege::ListTags);
		$suppliedFilter = InputHelper::get('filter');

		$tags = Model_Tag::getEntitiesRows($suppliedFilter, null, null);
		$this->context->transport->tags = $tags;

		if ($this->context->json)
		{
			$this->context->transport->tags = array_values(array_map(function($tag) {
				return ['name' => $tag['name'], 'count' => $tag['post_count']];
			}, $this->context->transport->tags));
			usort($this->context->transport->tags, function($a, $b) {
				return $a['count'] > $b['count'] ? -1 : 1;
			});
		}
		else
		{
			uasort($this->context->transport->tags, function($a, $b) {
				return strnatcasecmp($a['name'], $b['name']);
			});
		}
	}

	/**
	* @route /tags/merge
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
				R::store($post);
			}
			R::trash($sourceTag);

			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('tag', 'list'));
			LogHelper::logEvent('tag-merge', '{user} merged {source} with {target}', ['source' => TextHelper::reprTag($suppliedSourceTag), 'target' => TextHelper::reprTag($suppliedTargetTag)]);
			StatusHelper::success();
		}
	}

	/**
	* @route /tags/rename
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
			$suppliedSourceTag = InputHelper::get('source-tag');
			$suppliedSourceTag = Model_Tag::validateTag($suppliedSourceTag);

			$suppliedTargetTag = InputHelper::get('target-tag');
			$suppliedTargetTag = Model_Tag::validateTag($suppliedTargetTag);
			$targetTag = Model_Tag::locate($suppliedTargetTag, false);
			if ($targetTag)
				throw new SimpleException('Target tag already exists');

			$sourceTag = Model_Tag::locate($suppliedSourceTag);
			$sourceTag->name = $suppliedTargetTag;
			R::store($sourceTag);

			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('tag', 'list'));
			LogHelper::logEvent('tag-rename', '{user} renamed {source} to {target}', ['source' => TextHelper::reprTag($suppliedSourceTag), 'target' => TextHelper::reprTag($suppliedTargetTag)]);
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
			$suppliedTag = Model_Tag::validateTag($suppliedTag);
			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('post', 'list', ['source' => 'mass-tag', 'query' => urlencode($suppliedQuery), 'additionalInfo' => $suppliedTag]));
		}
	}
}
