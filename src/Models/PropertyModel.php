<?php
use \Chibi\Sql as Sql;

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
	static $database;

	public static function init()
	{
		self::$database = Core::getDatabase();
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
		$stmt = Sql\Statements::select();
		$stmt ->setColumn('*');
		$stmt ->setTable('property');
		foreach (self::$database->fetchAll($stmt) as $row)
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
		self::$database->transaction(function() use ($propertyId, $value)
		{
			$stmt = Sql\Statements::select();
			$stmt->setColumn('id');
			$stmt->setTable('property');
			$stmt->setCriterion(Sql\Functors::equals('prop_id', new Sql\Binding($propertyId)));
			$row = self::$database->fetchOne($stmt);

			if ($row)
			{
				$stmt = Sql\Statements::update();
				$stmt->setCriterion(Sql\Functors::equals('prop_id', new Sql\Binding($propertyId)));
			}
			else
			{
				$stmt = Sql\Statements::insert();
				$stmt->setColumn('prop_id', new Sql\Binding($propertyId));
			}
			$stmt->setTable('property');
			$stmt->setColumn('value', new Sql\Binding($value));

			self::$database->execute($stmt);

			self::$allProperties[$propertyId] = $value;
		});
	}
}
