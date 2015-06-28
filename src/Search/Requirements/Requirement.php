<?php
namespace Szurubooru\Search\Requirements;

class Requirement
{
    private $negated = false;
    private $type;
    private $value;

    public function isNegated()
    {
        return $this->negated;
    }

    public function setNegated($negated)
    {
        $this->negated = $negated;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue(IRequirementValue $value)
    {
        $this->value = $value;
    }
}
