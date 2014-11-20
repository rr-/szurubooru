<?php
namespace Szurubooru\Routes\Comments;
use Szurubooru\Controllers\ViewProxies\CommentViewProxy;
use Szurubooru\Controllers\ViewProxies\PostViewProxy;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\SearchServices\Filters\PostFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\SearchServices\Requirements\RequirementRangedValue;
use Szurubooru\Services\CommentService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

class GetComments extends AbstractCommentRoute
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
		return '/api/comments';
	}

	public function work()
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_COMMENTS);

		$filter = new PostFilter();
		$filter->setPageSize(10);
		$filter->setPageNumber($this->inputReader->page);
		$filter->setOrder([
			PostFilter::ORDER_LAST_COMMENT_TIME =>
			PostFilter::ORDER_DESC]);

		$this->postService->decorateFilterFromBrowsingSettings($filter);

		$requirement = new Requirement();
		$requirement->setValue(new RequirementRangedValue());
		$requirement->getValue()->setMinValue(1);
		$requirement->setType(PostFilter::REQUIREMENT_COMMENT_COUNT);
		$filter->addRequirement($requirement);

		$result = $this->postService->getFiltered($filter);
		$posts = $result->getEntities();

		$data = [];
		foreach ($posts as $post)
		{
			$data[] = [
				'post' => $this->postViewProxy->fromEntity($post),
				'comments' => $this->commentViewProxy->fromArray(
					array_reverse($this->commentService->getByPost($post)),
					$this->getCommentsFetchConfig()),
			];
		}

		return [
			'data' => $data,
			'pageSize' => $result->getPageSize(),
			'totalRecords' => $result->getTotalRecords()];
	}
}
