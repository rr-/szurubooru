<?php
namespace Szurubooru\Routes\Comments;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Services\CommentService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\ViewProxies\CommentViewProxy;
use Szurubooru\ViewProxies\PostViewProxy;

class EditComment extends AbstractCommentRoute
{
    private $privilegeService;
    private $postService;
    private $commentService;
    private $commentViewProxy;
    private $postViewProxy;
    private $inputReader;

    public function __construct(
        PrivilegeService $privilegeService,
        PostService $postService,
        CommentService $commentService,
        CommentViewProxy $commentViewProxy,
        PostViewProxy $postViewProxy,
        InputReader $inputReader)
    {
        $this->privilegeService = $privilegeService;
        $this->postService = $postService;
        $this->commentService = $commentService;
        $this->commentViewProxy = $commentViewProxy;
        $this->postViewProxy = $postViewProxy;
        $this->inputReader = $inputReader;
    }

    public function getMethods()
    {
        return ['PUT'];
    }

    public function getUrl()
    {
        return '/api/comments/:commentId';
    }

    public function work($args)
    {
        $comment = $this->commentService->getById($args['commentId']);

        $this->privilegeService->assertPrivilege(
            ($comment->getUser() && $this->privilegeService->isLoggedIn($comment->getUser()))
                ? Privilege::EDIT_OWN_COMMENTS
                : Privilege::EDIT_ALL_COMMENTS);

        $comment = $this->commentService->updateComment($comment, $this->inputReader->text);
        return ['comment' => $this->commentViewProxy->fromEntity($comment, $this->getCommentsFetchConfig())];
    }
}
