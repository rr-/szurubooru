<?php
class AbstractTest
{
	public $assert;

	public function __construct()
	{
		$this->assert = new Assert();
	}

	protected function mockUser()
	{
		$user = UserModel::spawn();
		$user->setAccessRank(new AccessRank(AccessRank::Registered));
		$user->setName('dummy');
		$user->passHash = UserModel::hashPassword('ble', $user->passSalt);
		return UserModel::save($user);
	}

	protected function mockPost()
	{
		$post = PostModel::spawn();
		$post->setType(new PostType(PostType::Image));
		return PostModel::save($post);
	}

	protected function login($user)
	{
		Auth::setCurrentUser($user);
	}

	protected function mockComment($owner)
	{
		$post = $this->mockPost();
		$comment = CommentModel::spawn();
		$comment->setPost($post);
		$comment->setCommenter($owner);
		$comment->text = 'test test';
		return CommentModel::save($comment);
	}
}
