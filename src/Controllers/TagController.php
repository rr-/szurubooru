<?php
class TagController
{
	/**
	* @route /tags
	*/
	public function listAction()
	{
		$this->context->stylesheets []= 'tag-list.css';
		$this->context->subTitle = 'tags';

		PrivilegesHelper::confirmWithException(Privilege::ListTags);
		$suppliedFilter = InputHelper::get('filter');

		$tags = Model_Tag::getEntities($suppliedFilter, null, null);
		$this->context->transport->tags = $tags;

		if ($this->context->json)
			$this->context->transport->tags = array_values(array_map(function($tag) {
				return ['name' => $tag->name, 'count' => $tag->getPostCount()];
			}, $this->context->transport->tags));
	}

	/**
	* @route /tags/merge
	*/
	public function mergeAction()
	{
		PrivilegesHelper::confirmWithException(Privilege::MergeTags);
		if (InputHelper::get('submit'))
		{
			$sourceTag = Model_Tag::locate(InputHelper::get('source-tag'));
			$targetTag = Model_Tag::locate(InputHelper::get('target-tag'));

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
			$this->view->context->success = true;
		}
	}

	/**
	* @route /tags/rename
	*/
	public function renameAction()
	{
		PrivilegesHelper::confirmWithException(Privilege::MergeTags);
		if (InputHelper::get('submit'))
		{
			$suppliedSourceTag = InputHelper::get('source-tag');
			$suppliedSourceTag = Model_Tag::validateTag($suppliedSourceTag);

			$suppliedTargetTag = InputHelper::get('target-tag');
			$suppliedTargetTag = Model_Tag::validateTag($suppliedTargetTag);

			$sourceTag = Model_Tag::locate($suppliedSourceTag);
			$sourceTag->name = $suppliedTargetTag;
			R::store($sourceTag);

			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('tag', 'list'));
			$this->context->transport->success = true;
		}
	}
}
