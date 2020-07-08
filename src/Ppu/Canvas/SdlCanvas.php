<?php
namespace Nes\Ppu\Canvas;

class SdlCanvas implements CanvasInterface
{
    private $serial = 0;

    public $sdl;
    public $renderer;
    public $pixels;

    public function __construct() {
  		$this->sdl = SDL_CreateWindow('PHP Chip 8', SDL_WINDOWPOS_UNDEFINED, SDL_WINDOWPOS_UNDEFINED, 512, 448, SDL_WINDOW_SHOWN);
  		$this->renderer = SDL_CreateRenderer($this->sdl, 0, SDL_RENDERER_SOFTWARE);
  		SDL_SetRenderDrawColor($this->renderer, 0, 0, 0, 255);
  		SDL_RenderClear($this->renderer);
    }
    public function draw(array $frameBuffer)
    {
        for ($y = 0; $y < 224; $y++) {
            for ($x = 0; $x < 256; $x++) {
                $index = ($x + ($y * 0x100)) * 4;
                if(!isset($this->pixels[$x][$y])) {
                  $this->pixels[$x][$y] = new \SDL_Rect($x*2, $y*2, 2, 2);
                }
                SDL_SetRenderDrawColor($this->renderer, $frameBuffer[$index], $frameBuffer[$index + 1], $frameBuffer[$index + 2], 155);
                SDL_RenderFillRect($this->renderer, $this->pixels[$x][$y]);
            }
        }
        SDL_RenderPresent($this->renderer);
    }
}
