<?php
class GetPostJob extends AbstractPostEditJob
{
	public function execute()
	{
		$post = $this->post;

		//todo: refactor this so that requiresPrivilege can accept multiple privileges
		if ($this->post->hidden)
			Access::assert(Privilege::ViewPost, 'hidden');
		Access::assert(Privilege::ViewPost);
		Access::assert(Privilege::ViewPost, PostSafety::toString($this->post->safety));

		CommentModel::preloadCommenters($post->getComments());

		return $post;
	}

	public function requiresPrivilege()
	{
		//temporarily enforced in execute
		return false;
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
