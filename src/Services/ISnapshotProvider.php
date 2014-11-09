<?php
namespace Szurubooru\Services;
use Szurubooru\Entities\Entity;

interface ISnapshotProvider
{
	public function getCreationSnapshot(Entity $entity);
	public function getChangeSnapshot(Entity $entity);
	public function getDeleteSnapshot(Entity $entity);
}
