<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\GlobalParamDao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\GlobalParam;
use Szurubooru\Entities\Post;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PostHistoryService;
use Szurubooru\Services\TimeService;
use Szurubooru\Validator;

class PostFeatureService
{
	private $transactionManager;
	private $postDao;
	private $userDao;
	private $globalParamDao;
	private $authService;
	private $timeService;
	private $postHistoryService;

	public function __construct(
		TransactionManager $transactionManager,
		PostDao $postDao,
		UserDao $userDao,
		GlobalParamDao $globalParamDao,
		AuthService $authService,
		TimeService $timeService,
		PostHistoryService $postHistoryService)
	{
		$this->transactionManager = $transactionManager;
		$this->postDao = $postDao;
		$this->userDao = $userDao;
		$this->globalParamDao = $globalParamDao;
		$this->authService = $authService;
		$this->timeService = $timeService;
		$this->postHistoryService = $postHistoryService;
	}

	public function getFeaturedPost()
	{
		$transactionFunc = function()
		{
			$globalParam = $this->globalParamDao->findByKey(GlobalParam::KEY_FEATURED_POST);
			if (!$globalParam)
				return null;
			return $this->postDao->findById($globalParam->getValue());
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getFeaturedPostUser()
	{
		$transactionFunc = function()
		{
			$globalParam = $this->globalParamDao->findByKey(GlobalParam::KEY_FEATURED_POST_USER);
			if (!$globalParam)
				return null;
			return $this->userDao->findById($globalParam->getValue());
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function featurePost(Post $post)
	{
		$transactionFunc = function() use ($post)
		{
			$previousFeaturedPost = $this->getFeaturedPost();

			if (($previousFeaturedPost === null) or ($previousFeaturedPost->getId() !== $post->getId()))
			{
				$post->setLastFeatureTime($this->timeService->getCurrentTime());
				$post->setFeatureCount($post->getFeatureCount() + 1);
				$this->postDao->save($post);
			}

			$globalParam = new GlobalParam();
			$globalParam->setKey(GlobalParam::KEY_FEATURED_POST);
			$globalParam->setValue($post->getId());
			$this->globalParamDao->save($globalParam);

			$globalParam = new GlobalParam();
			$globalParam->setKey(GlobalParam::KEY_FEATURED_POST_USER);
			$globalParam->setValue($this->authService->getLoggedInUser()->getId());
			$this->globalParamDao->save($globalParam);

			if ($previousFeaturedPost)
				$this->postHistoryService->savePostChange($previousFeaturedPost);
			$this->postHistoryService->savePostChange($post);
		};
		$this->transactionManager->commit($transactionFunc);
	}
}
