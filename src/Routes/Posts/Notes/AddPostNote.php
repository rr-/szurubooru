<?php
namespace Szurubooru\Routes\Posts\Notes;
use Szurubooru\Controllers\ViewProxies\PostNoteViewProxy;
use Szurubooru\FormData\PostNoteFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Routes\Posts\AbstractPostRoute;
use Szurubooru\Services\PostNotesService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

class AddPostNote extends AbstractPostRoute
{
	private $inputReader;
	private $postService;
	private $postNotesService;
	private $privilegeService;
	private $postNoteViewProxy;

	public function __construct(
		InputReader $inputReader,
		PostService $postService,
		PostNotesService $postNotesService,
		PrivilegeService $privilegeService,
		PostNoteViewProxy $postNoteViewProxy)
	{
		$this->inputReader = $inputReader;
		$this->postService = $postService;
		$this->postNotesService = $postNotesService;
		$this->privilegeService = $privilegeService;
		$this->postNoteViewProxy = $postNoteViewProxy;
	}

	public function getMethods()
	{
		return ['POST'];
	}

	public function getUrl()
	{
		return '/api/notes/:postNameOrId';
	}

	public function work($args)
	{
		$post = $this->postService->getByNameOrId($args['postNameOrId']);

		$this->privilegeService->assertPrivilege(Privilege::ADD_POST_NOTES);

		$formData = new PostNoteFormData($this->inputReader);
		$postNote = $this->postNotesService->createPostNote($post, $formData);
		return $this->postNoteViewProxy->fromEntity($postNote);
	}
}
