<?php
namespace Szurubooru\Routes\Posts\Notes;
use Szurubooru\Privilege;
use Szurubooru\Routes\Posts\AbstractPostRoute;
use Szurubooru\Services\PostNotesService;
use Szurubooru\Services\PrivilegeService;

class DeletePostNote extends AbstractPostRoute
{
    private $postNotesService;
    private $privilegeService;

    public function __construct(
        PostNotesService $postNotesService,
        PrivilegeService $privilegeService)
    {
        $this->postNotesService = $postNotesService;
        $this->privilegeService = $privilegeService;
    }

    public function getMethods()
    {
        return ['DELETE'];
    }

    public function getUrl()
    {
        return '/api/notes/:postNoteId';
    }

    public function work($args)
    {
        $postNote = $this->postNotesService->getById($args['postNoteId']);
        $this->privilegeService->assertPrivilege(Privilege::DELETE_POST_NOTES);
        $this->postNotesService->deletePostNote($postNote);
    }
}
