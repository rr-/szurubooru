<?php
namespace Szurubooru\Routes\Posts\Notes;
use Szurubooru\Controllers\ViewProxies\PostNoteViewProxy;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Routes\Posts\AbstractPostRoute;
use Szurubooru\Services\PostNotesService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

class DeletePostNote extends AbstractPostRoute
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
		return ['DELETE'];
	}

	public function getUrl()
	{
		return '/api/notes/:postNoteId';
	}

	public function work()
	{
		$postNote = $this->postNotesService->getById($this->getArgument('postNoteId'));
		$this->privilegeService->assertPrivilege(Privilege::DELETE_POST_NOTES);
		return $this->postNotesService->deletePostNote($postNote);
	}
}
