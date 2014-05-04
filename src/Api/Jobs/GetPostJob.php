<?php
class GetPostJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;

		CommentModel::preloadCommenters($post->getComments());

		return $post;
	}

	public function requiresPrivilege()
	{
		$post = $this->post;
		$privileges = [];

		if ($post->hidden)
			$privileges []= new Privilege(Privilege::ViewPost, 'hidden');

		$privileges []= new Privilege(Privilege::ViewPost, PostSafety::toString($post->safety));

		return $privileges;
	}
}
