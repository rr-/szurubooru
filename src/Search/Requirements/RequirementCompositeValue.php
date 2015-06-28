<?php
namespace Szurubooru\Search\Requirements;

class RequirementCompositeValue implements IRequirementValue
{
    private $values = [];

    public function __construct(array $values = [])
    {
        $this->setValues($values);
    }

    public function getValues()
    {
        return $this->values;
    }

    public function setValues(array $values)
    {
        $this->values = $values;
    }
}
