<?php
class EditCommentJob extends AbstractJob
{
	protected $comment;

	public function prepare($arguments)
	{
		$this->comment = CommentModel::findById($arguments['comment-id']);
	}

	public function execute($arguments)
	{
		$user = Auth::getCurrentUser();
		$comment = $this->comment;

		$comment->commentDate = time();
		$comment->text = CommentModel::validateText($arguments['text']);

		CommentModel::save($comment);
		LogHelper::log('{user} edited comment in {post}', [
			'user' => TextHelper::reprUser($user),
			'post' => TextHelper::reprPost($comment->getPost())]);

		return $comment;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::EditComment,
			Access::getIdentity($this->comment->getCommenter())
		];
	}

	public function requiresAuthentication()
	{
		return true;
	}

	public function requiresConfirmedEmail()
	{
		return getConfig()->registration->needEmailForCommenting;
	}
}
