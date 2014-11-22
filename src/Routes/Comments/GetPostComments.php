<?php
namespace Szurubooru\Routes\Comments;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\SearchServices\Filters\CommentFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\SearchServices\Requirements\RequirementSingleValue;
use Szurubooru\Services\CommentService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\ViewProxies\CommentViewProxy;
use Szurubooru\ViewProxies\PostViewProxy;

class GetPostComments extends AbstractCommentRoute
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
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/comments/:postNameOrId';
	}

	public function work($args)
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_COMMENTS);
		$post = $this->postService->getByNameOrId($args['postNameOrId']);

		$filter = new CommentFilter();
		$filter->setOrder([
			CommentFilter::ORDER_ID =>
			CommentFilter::ORDER_ASC]);

		$requirement = new Requirement();
		$requirement->setValue(new RequirementSingleValue($post->getId()));
		$requirement->setType(CommentFilter::REQUIREMENT_POST_ID);
		$filter->addRequirement($requirement);

		$result = $this->commentService->getFiltered($filter);
		$entities = $this->commentViewProxy->fromArray($result->getEntities(), $this->getCommentsFetchConfig());
		return ['data' => $entities];
	}
}
