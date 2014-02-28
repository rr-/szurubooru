<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

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
			$stmt = new Sql\SelectStatement();
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

	public static function featureNewPost()
	{
		$stmt = (new Sql\SelectStatement)
			->setColumn('id')
			->setTable('post')
			->setCriterion((new Sql\ConjunctionFunctor)
				->add(new Sql\EqualsFunctor('type', new Sql\Binding(PostType::Image)))
				->add(new Sql\EqualsFunctor('safety', new Sql\Binding(PostSafety::Safe))))
			->setOrderBy(new Sql\RandomFunctor(), Sql\SelectStatement::ORDER_DESC);
		$featuredPostId = Database::fetchOne($stmt)['id'];
		if (!$featuredPostId)
			return null;

		self::set(self::FeaturedPostId, $featuredPostId);
		self::set(self::FeaturedPostDate, time());
		self::set(self::FeaturedPostUserName, null);
		return PostModel::findById($featuredPostId);
	}
}
