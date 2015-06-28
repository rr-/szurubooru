<?php
namespace Szurubooru\Routes\Tags;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TagService;
use Szurubooru\ViewProxies\TagViewProxy;

class GetTagSiblings extends AbstractTagRoute
{
    private $privilegeService;
    private $tagService;
    private $tagViewProxy;

    public function __construct(
        PrivilegeService $privilegeService,
        TagService $tagService,
        TagViewProxy $tagViewProxy)
    {
        $this->privilegeService = $privilegeService;
        $this->tagService = $tagService;
        $this->tagViewProxy = $tagViewProxy;
    }

    public function getMethods()
    {
        return ['GET'];
    }

    public function getUrl()
    {
        return '/api/tags/:tagName/siblings';
    }

    public function work($args)
    {
        $tagName = $args['tagName'];
        $this->privilegeService->assertPrivilege(Privilege::LIST_TAGS);
        $tag = $this->tagService->getByName($tagName);
        $result = $this->tagService->getSiblings($tagName);
        $entities = $this->tagViewProxy->fromArray($result);
        return [
            'data' => $entities,
        ];
    }
}
