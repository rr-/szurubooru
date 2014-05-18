<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

final class PropertyModel implements IModel
{
	const FeaturedPostId = 0;
	const FeaturedPostUserName = 1;
	const FeaturedPostUnixTime = 2;
	const DbVersion = 3;
	const PostSpaceUsage = 4;
	const PostSpaceUsageUnixTime = 5;
	const EngineVersion = 6;

	static $allProperties;
	static $loaded;

	public static function init()
	{
		self::$allProperties = null;
		self::$loaded = false;
	}

	public static function getTableName()
	{
		return 'property';
	}

	public static function loadIfNecessary()
	{
		if (self::$loaded)
			return;

		self::$loaded = true;
		self::$allProperties = [];
		$stmt = new Sql\SelectStatement();
		$stmt ->setColumn('*');
		$stmt ->setTable('property');
		foreach (Database::fetchAll($stmt) as $row)
			self::$allProperties[$row['prop_id']] = $row['value'];
	}

	public static function get($propertyId)
	{
		self::loadIfNecessary();
		return isset(self::$allProperties[$propertyId])
			? self::$allProperties[$propertyId]
			: null;
	}

	public static function set($propertyId, $value)
	{
		self::loadIfNecessary();
		Database::transaction(function() use ($propertyId, $value)
		{
			$stmt = new Sql\SelectStatement();
			$stmt->setColumn('id');
			$stmt->setTable('property');
			$stmt->setCriterion(new Sql\EqualsFunctor('prop_id', new Sql\Binding($propertyId)));
			$row = Database::fetchOne($stmt);

			if ($row)
			{
				$stmt = new Sql\UpdateStatement();
				$stmt->setCriterion(new Sql\EqualsFunctor('prop_id', new Sql\Binding($propertyId)));
			}
			else
			{
				$stmt = new Sql\InsertStatement();
				$stmt->setColumn('prop_id', new Sql\Binding($propertyId));
			}
			$stmt->setTable('property');
			$stmt->setColumn('value', new Sql\Binding($value));

			Database::exec($stmt);

			self::$allProperties[$propertyId] = $value;
		});
	}
}
