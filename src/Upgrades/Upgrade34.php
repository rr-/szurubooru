<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade34 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();
        $pdo->exec('ALTER TABLE snapshots CHANGE operation operation INT(1) NOT NULL');

        foreach ($pdo->from('snapshots') as $row)
        {
            $newDifference = ['+' => [], '-' => []];
            $oldDifference = json_decode($row['dataDifference'], true);
            foreach (['+', '-'] as $type)
            {
                foreach ($oldDifference[$type] as $item)
                {
                    $target = &$newDifference[$type][$item[0]];
                    if (isset($target))
                    {
                        if (!is_array($target))
                            $target = [$target];
                        $target[] = $item[1];
                    }
                    else
                    {
                        $target = $item[1];
                    }
                }
            }
            $newDifference = json_encode($newDifference);

            $pdo->update('snapshots')
                ->set([
                    'data' => gzdeflate($row['data']),
                    'dataDifference' => gzdeflate($newDifference)])
                ->where('id', $row['id'])
                ->execute();
        }
    }
}
