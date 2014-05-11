<?php
class MergeTagsJob extends AbstractJob
{
	public function execute()
	{
		$sourceTag = $this->getArgument(JobArgs::ARG_SOURCE_TAG_NAME);
		$targetTag = $this->getArgument(JobArgs::ARG_TARGET_TAG_NAME);

		TagModel::removeUnused();
		TagModel::merge($sourceTag, $targetTag);

		Logger::log('{user} merged {source} with {target}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'source' => TextHelper::reprTag($sourceTag),
			'target' => TextHelper::reprTag($targetTag)]);
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::MergeTags);
	}
}
