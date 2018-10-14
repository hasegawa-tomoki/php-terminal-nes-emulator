<?php
namespace Nes\Ppu;

class Tile
{
    public $pattern;
    public $paletteId;
    public $scrollX;
    public $scrollY;

    public function __construct(array $pattern, int $paletteId, int $scrollX, int $scrollY)
    {
        $this->pattern = $pattern;
        $this->paletteId = $paletteId;
        $this->scrollX = $scrollX;
        $this->scrollY = $scrollY;
    }
}
