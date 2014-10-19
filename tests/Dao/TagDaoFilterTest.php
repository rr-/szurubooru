<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\TagDao;
use Szurubooru\Entities\Tag;
use Szurubooru\SearchServices\Filters\TagFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\SearchServices\Requirements\RequirementSingleValue;
use Szurubooru\SearchServices\Result;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class TagDaoFilterTest extends AbstractDatabaseTestCase
{
	public function testCategories()
	{
		$tag1 = $this->getTestTag('test 1');
		$tag2 = $this->getTestTag('test 2');
		$tag3 = $this->getTestTag('test 3');
		$tag1->setCategory(null);
		$tag2->setCategory('misc');
		$tag3->setCategory('other');
		$tagDao = $this->getTagDao();
		$tagDao->save($tag1);
		$tagDao->save($tag2);
		$tagDao->save($tag3);

		$searchFilter = new TagFilter();
		$requirement = new Requirement();
		$requirement->setType(TagFilter::REQUIREMENT_CATEGORY);
		$requirement->setValue(new RequirementSingleValue('misc'));
		$requirement->setNegated(true);
		$searchFilter->addRequirement($requirement);
		$result = $tagDao->findFiltered($searchFilter);
		$this->assertEquals(2, $result->getTotalRecords());
		$this->assertEntitiesEqual([$tag3, $tag1], array_values($result->getEntities()));
	}

	private function getTagDao()
	{
		return new TagDao($this->databaseConnection);
	}

	private function getTestTag($name)
	{
		$tag = new Tag();
		$tag->setName($name);
		$tag->setCreationTime(date('c'));
		return $tag;
	}
}
