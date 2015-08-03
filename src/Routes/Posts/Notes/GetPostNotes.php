<?php
namespace Szurubooru\Routes\Posts\Notes;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Routes\Posts\AbstractPostRoute;
use Szurubooru\Services\PostNotesService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\ViewProxies\PostNoteViewProxy;

class GetPostNotes extends AbstractPostRoute
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
        return ['GET'];
    }

    public function getUrl()
    {
        return '/api/notes/:postNameOrId';
    }

    public function work($args)
    {
        $post = $this->postService->getByNameOrId($args['postNameOrId']);
        $postNotes = $this->postNotesService->getByPost($post);
        return ['notes' => $this->postNoteViewProxy->fromArray($postNotes)];
    }
}
