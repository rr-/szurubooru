<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\PostNoteDao;
use Szurubooru\Entities\Post;

class PostNotesService
{
	private $postNoteDao;

	public function __construct(PostNoteDao $postNoteDao)
	{
		$this->postNoteDao = $postNoteDao;
	}

	public function getPostNotes(Post $post)
	{
		return $this->postNoteDao->findByPostId($post->getId());
	}
}
