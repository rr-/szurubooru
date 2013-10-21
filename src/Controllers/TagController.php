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

		$dbQuery = R::$f->begin();
		$dbQuery->select('tag.*, COUNT(1) AS count');
		$dbQuery->from('tag');
		$dbQuery->innerJoin('post_tag');
		$dbQuery->on('tag.id = post_tag.tag_id');
		if ($suppliedFilter)
		{
			if (strlen($suppliedFilter) >= 3)
				$suppliedFilter = '%' . $suppliedFilter;
			$suppliedFilter .= '%';
			$dbQuery->where('LOWER(tag.name) LIKE LOWER(?)')->put($suppliedFilter);
		}
		$dbQuery->groupBy('tag.id');
		$dbQuery->orderBy('LOWER(tag.name)')->asc();
		if ($suppliedFilter)
			$dbQuery->limit(15);
		$rows = $dbQuery->get();
		$tags = R::convertToBeans('tag', $rows);

		$tags = [];
		$tagDistribution = [];
		foreach ($rows as $row)
		{
			$tags []= strval($row['name']);
			$tagDistribution[$row['name']] = intval($row['count']);
		}

		$this->context->transport->tags = $tags;
		$this->context->transport->tagDistribution = $tagDistribution;
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
