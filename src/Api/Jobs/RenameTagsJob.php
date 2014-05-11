<?php
class RenameTagsJob extends AbstractJob
{
	public function execute()
	{
		$sourceTag = $this->getArgument(JobArgs::ARG_SOURCE_TAG_NAME);
		$targetTag = $this->getArgument(JobArgs::ARG_TARGET_TAG_NAME);

		TagModel::removeUnused();
		TagModel::rename($sourceTag, $targetTag);

		Logger::log('{user} renamed {source} to {target}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'source' => TextHelper::reprTag($sourceTag),
			'target' => TextHelper::reprTag($targetTag)]);
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::RenameTags);
	}
}
