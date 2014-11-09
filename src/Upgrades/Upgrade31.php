<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade31 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		foreach (array_chunk(iterator_to_array($pdo->from('snapshots')), 100) as $chunk)
		{
			$pdo->beginTransaction();
			foreach ($chunk as $array)
			{
				$pdo->update('snapshots')->set([
					'data' => json_encode(unserialize($array['data'])),
					'dataDifference' => json_encode(unserialize($array['dataDifference']))
				])->where('id', $array['id'])->execute();
			}
			$pdo->commit();
		}
	}
}
