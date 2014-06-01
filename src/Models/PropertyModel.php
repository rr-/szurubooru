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
		$stmt = Sql\Statements::select();
		$stmt ->setColumn('*');
		$stmt ->setTable('property');
		foreach (Core::getDatabase()->fetchAll($stmt) as $row)
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
		Core::getDatabase()->transaction(function() use ($propertyId, $value)
		{
			$stmt = Sql\Statements::select();
			$stmt->setColumn('id');
			$stmt->setTable('property');
			$stmt->setCriterion(Sql\Functors::equals('prop_id', new Sql\Binding($propertyId)));
			$row = Core::getDatabase()->fetchOne($stmt);

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

			Core::getDatabase()->execute($stmt);

			self::$allProperties[$propertyId] = $value;
		});
	}
}
