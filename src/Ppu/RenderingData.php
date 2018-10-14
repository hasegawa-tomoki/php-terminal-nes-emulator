<?php
namespace Nes\Ppu;

class RenderingData
{
    /** @var int[] */
    public $palette;
    /** @var \Nes\Ppu\Tile[] */
    public $background;
    /** @var \Nes\Ppu\SpriteWithAttribute[] */
    public $sprites;

    public function __construct($palette, $background, $sprites)
    {
        $this->palette = $palette;
        $this->background = $background;
        $this->sprites = $sprites;
    }
}
