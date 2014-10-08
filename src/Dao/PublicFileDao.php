<?php
namespace Szurubooru\Dao;
use Szurubooru\Config;
use Szurubooru\Dao\FileDao;
use Szurubooru\Dao\IFileDao;

class PublicFileDao extends FileDao implements IFileDao
{
	public function __construct(Config $config)
	{
		parent::__construct($config->getPublicDataDirectory());
	}
}
