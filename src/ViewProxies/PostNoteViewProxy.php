<?php
namespace Szurubooru\ViewProxies;

class PostNoteViewProxy extends AbstractViewProxy
{
    public function fromEntity($postNote, $config = [])
    {
        $result = new \StdClass;
        if ($postNote)
        {
            $result->id = $postNote->getId();
            $result->postId = $postNote->getPostId();
            $result->text = $postNote->getText();
            $result->left = $postNote->getLeft();
            $result->top = $postNote->getTop();
            $result->width = $postNote->getWidth();
            $result->height = $postNote->getHeight();
        }
        return $result;
    }
}
