<?php
namespace Szurubooru\Routes\Tags;
use Szurubooru\FormData\TagEditFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TagService;
use Szurubooru\ViewProxies\TagViewProxy;

class UpdateTag extends AbstractTagRoute
{
    private $privilegeService;
    private $tagService;
    private $tagViewProxy;
    private $inputReader;

    public function __construct(
        PrivilegeService $privilegeService,
        TagService $tagService,
        TagViewProxy $tagViewProxy,
        InputReader $inputReader)
    {
        $this->privilegeService = $privilegeService;
        $this->tagService = $tagService;
        $this->tagViewProxy = $tagViewProxy;
        $this->inputReader = $inputReader;
    }

    public function getMethods()
    {
        return ['PUT'];
    }

    public function getUrl()
    {
        return '/api/tags/:tagName';
    }

    public function work($args)
    {
        $tag = $this->tagService->getByName($args['tagName']);
        $formData = new TagEditFormData($this->inputReader);

        if ($formData->name !== null)
            $this->privilegeService->assertPrivilege(Privilege::CHANGE_TAG_NAME);

        if ($formData->category !== null)
            $this->privilegeService->assertPrivilege(Privilege::CHANGE_TAG_CATEGORY);

        if ($formData->banned !== null)
            $this->privilegeService->assertPrivilege(Privilege::BAN_TAGS);

        if ($formData->implications !== null)
            $this->privilegeService->assertPrivilege(Privilege::CHANGE_TAG_IMPLICATIONS);

        if ($formData->suggestions !== null)
            $this->privilegeService->assertPrivilege(Privilege::CHANGE_TAG_SUGGESTIONS);

        $tag = $this->tagService->updateTag($tag, $formData);
        return $this->tagViewProxy->fromEntity($tag, $this->getFullFetchConfig());
    }
}
