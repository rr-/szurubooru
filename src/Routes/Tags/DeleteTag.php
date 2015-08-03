<?php
namespace Szurubooru\Routes\Tags;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TagService;

class DeleteTag extends AbstractTagRoute
{
    private $privilegeService;
    private $tagService;

    public function __construct(
        PrivilegeService $privilegeService,
        TagService $tagService)
    {
        $this->privilegeService = $privilegeService;
        $this->tagService = $tagService;
    }

    public function getMethods()
    {
        return ['DELETE'];
    }

    public function getUrl()
    {
        return '/api/tags/:tagName';
    }

    public function work($args)
    {
        $tag = $this->tagService->getByName($args['tagName']);
        $this->privilegeService->assertPrivilege(Privilege::DELETE_TAGS);
        $this->tagService->deleteTag($tag);
    }
}
