<?php
namespace Nes\Ppu;

class SpriteWithAttribute
{
    public $sprite;
    public $x;
    public $y;
    public $attribute;
    public $id;

    public function __construct(array $sprite, int $x, int $y, int $attribute, int $id)
    {
        $this->sprite = $sprite;
        $this->x = $x;
        $this->y = $y;
        $this->attribute = $attribute;
        $this->id = $id;
    }
}
