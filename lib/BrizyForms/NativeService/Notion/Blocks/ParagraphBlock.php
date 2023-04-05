<?php namespace Notion\Blocks;

use Notion\BlockBase;
use Notion\RichText;

class ParagraphBlock extends BlockBase
{
    public $type = 'paragraph';

    public function __construct(RichText $richText)
    {
        $this->typeConfiguration = [
            'text' => [$richText->get()],
            //'children' => [], // Todo: Children for paragraph block
        ];
    }
}
