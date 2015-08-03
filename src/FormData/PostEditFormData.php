<?php
namespace Szurubooru\FormData;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\IValidatable;
use Szurubooru\Validator;

class PostEditFormData implements IValidatable
{
    public $content;
    public $thumbnail;
    public $safety;
    public $source;
    public $tags;
    public $relations;
    public $flags;

    public $lastEditTime;

    public function __construct($inputReader = null)
    {
        if ($inputReader !== null)
        {
            $this->content = $inputReader->readFile('content');
            $this->thumbnail = $inputReader->readFile('thumbnail');
            if ($inputReader->safety)
                $this->safety = EnumHelper::postSafetyFromString($inputReader->safety);
            if ($inputReader->source !== null)
                $this->source = $inputReader->source;
            $this->tags = preg_split('/[\s+]/', $inputReader->tags);
            if ($inputReader->relations !== null)
                $this->relations = array_filter(preg_split('/[\s+]/', $inputReader->relations));
            $this->lastEditTime = $inputReader->lastEditTime;
            $this->flags = new \StdClass;
            $this->flags->loop = !empty($inputReader->loop);
        }
    }

    public function validate(Validator $validator)
    {
        $validator->validatePostTags($this->tags);

        if ($this->source !== null)
            $validator->validatePostSource($this->source);

        if ($this->relations)
        {
            foreach ($this->relations as $relatedPostId)
                $validator->validateNumber($relatedPostId);
        }
    }
}
