<?php
class PropertyModel implements IModel
{
	const FeaturedPostId = 0;
	const FeaturedPostUserName = 1;
	const FeaturedPostDate = 2;
	const DbVersion = 3;

	static $allProperties = null;
	static $loaded = false;

	public static function getTableName()
	{
		return 'property';
	}

	public static function loadIfNecessary()
	{
		if (!self::$loaded)
		{
			self::$loaded = true;
			self::$allProperties = [];
			$stmt = new SqlSelectStatement();
			$stmt ->setColumn('*');
			$stmt ->setTable('property');
			foreach (Database::fetchAll($stmt) as $row)
				self::$allProperties[$row['prop_id']] = $row['value'];
		}
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
			$stmt = new SqlSelectStatement();
			$stmt->setColumn('id');
			$stmt->setTable('property');
			$stmt->setCriterion(new SqlEqualsOperator('prop_id', new SqlBinding($propertyId)));
			$row = Database::fetchOne($stmt);

			if ($row)
			{
				$stmt = new SqlUpdateStatement();
				$stmt->setCriterion(new SqlEqualsOperator('prop_id', new SqlBinding($propertyId)));
			}
			else
			{
				$stmt = new SqlInsertStatement();
				$stmt->setColumn('prop_id', new SqlBinding($propertyId));
			}
			$stmt->setTable('property');
			$stmt->setColumn('value', new SqlBinding($value));

			Database::exec($stmt);

			self::$allProperties[$propertyId] = $value;
		});
	}
}
