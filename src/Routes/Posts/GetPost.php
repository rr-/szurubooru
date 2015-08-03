<?php
namespace Szurubooru\Routes\Posts;
use Szurubooru\Privilege;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\ViewProxies\PostViewProxy;

class GetPost extends AbstractPostRoute
{
    private $privilegeService;
    private $postService;
    private $postViewProxy;

    public function __construct(
        PrivilegeService $privilegeService,
        PostService $postService,
        PostViewProxy $postViewProxy)
    {
        $this->privilegeService = $privilegeService;
        $this->postService = $postService;
        $this->postViewProxy = $postViewProxy;
    }

    public function getMethods()
    {
        return ['GET'];
    }

    public function getUrl()
    {
        return '/api/posts/:postNameOrId';
    }

    public function work($args)
    {
        $this->privilegeService->assertPrivilege(Privilege::VIEW_POSTS);

        $post = $this->postService->getByNameOrId($args['postNameOrId']);
        return ['post' => $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig())];
    }
}
