<?php
namespace Szurubooru\Controllers;
use Szurubooru\Controllers\ViewProxies\PostNoteViewProxy;
use Szurubooru\FormData\PostNoteFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Router;
use Szurubooru\Services\PostNotesService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

final class PostNotesController extends AbstractController
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

	public function registerRoutes(Router $router)
	{
		$router->get('/api/notes/:postNameOrId', [$this, 'getPostNotes']);
		$router->post('/api/notes/:postNameOrId', [$this, 'addPostNote']);
		$router->put('/api/notes/:postNoteId', [$this, 'editPostNote']);
		$router->delete('/api/notes/:postNoteId', [$this, 'deletePostNote']);
	}

	public function getPostNotes($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);
		$postNotes = $this->postNotesService->getByPost($post);
		return $this->postNoteViewProxy->fromArray($postNotes);
	}

	public function addPostNote($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);

		$this->privilegeService->assertPrivilege(Privilege::ADD_POST_NOTES);

		$formData = new PostNoteFormData($this->inputReader);
		$postNote = $this->postNotesService->createPostNote($post, $formData);
		return $this->postNoteViewProxy->fromEntity($postNote);
	}

	public function editPostNote($postNoteId)
	{
		$postNote = $this->postNotesService->getById($postNoteId);

		$this->privilegeService->assertPrivilege(Privilege::EDIT_POST_NOTES);

		$formData = new PostNoteFormData($this->inputReader);
		$postNote = $this->postNotesService->updatePostNote($postNote, $formData);
		return $this->postNoteViewProxy->fromEntity($postNote);
	}

	public function deletePostNote($postNoteId)
	{
		$postNote = $this->postNotesService->getById($postNoteId);
		$this->privilegeService->assertPrivilege(Privilege::DELETE_POST_NOTES);
		return $this->postNotesService->deletePostNote($postNote);
	}
}
