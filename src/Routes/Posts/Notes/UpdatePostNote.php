<?php
namespace Szurubooru\Routes\Posts\Notes;
use Szurubooru\Controllers\ViewProxies\PostNoteViewProxy;
use Szurubooru\FormData\PostNoteFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Routes\Posts\AbstractPostRoute;
use Szurubooru\Services\PostNotesService;
use Szurubooru\Services\PrivilegeService;

class UpdatePostNote extends AbstractPostRoute
{
	private $inputReader;
	private $postNotesService;
	private $privilegeService;
	private $postNoteViewProxy;

	public function __construct(
		InputReader $inputReader,
		PostNotesService $postNotesService,
		PrivilegeService $privilegeService,
		PostNoteViewProxy $postNoteViewProxy)
	{
		$this->inputReader = $inputReader;
		$this->postNotesService = $postNotesService;
		$this->privilegeService = $privilegeService;
		$this->postNoteViewProxy = $postNoteViewProxy;
	}

	public function getMethods()
	{
		return ['PUT'];
	}

	public function getUrl()
	{
		return '/api/notes/:postNoteId';
	}

	public function work($args)
	{
		$postNote = $this->postNotesService->getById($args['postNoteId']);

		$this->privilegeService->assertPrivilege(Privilege::EDIT_POST_NOTES);

		$formData = new PostNoteFormData($this->inputReader);
		$postNote = $this->postNotesService->updatePostNote($postNote, $formData);
		return $this->postNoteViewProxy->fromEntity($postNote);
	}
}
