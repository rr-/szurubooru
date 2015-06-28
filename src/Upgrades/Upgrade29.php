<?php
namespace Szurubooru\Upgrades;
use Szurubooru\Dao\TagDao;
use Szurubooru\DatabaseConnection;
use Szurubooru\Services\TagHistoryService;

class Upgrade29 implements IUpgrade
{
    private $tagDao;
    private $tagHistoryService;

    public function __construct(TagDao $tagDao, TagHistoryService $tagHistoryService)
    {
        $this->tagDao = $tagDao;
        $this->tagHistoryService = $tagHistoryService;
    }

    public function run(DatabaseConnection $databaseConnection)
    {
        foreach ($this->tagDao->findAll() as $tag)
        {
            $this->tagHistoryService->saveTagChange($tag);
        }
    }
}
