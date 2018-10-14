<?php
namespace Nes\Ppu\Canvas;

class PngCanvas implements CanvasInterface
{
    private $serial = 0;

    public function draw(array $frameBuffer)
    {
        $image = imagecreatetruecolor(256, 224);
        for ($y = 0; $y < 224; $y++) {
            for ($x = 0; $x < 256; $x++) {
                $index = ($x + ($y * 0x100)) * 4;
                $color = imagecolorallocate(
                    $image,
                    $frameBuffer[$index],
                    $frameBuffer[$index + 1],
                    $frameBuffer[$index + 2]
                );
                imagesetpixel($image, $x, $y, $color);
            }
        }
        if (! is_dir('screen')) {
            mkdir('screen');
        }
        imagepng($image, sprintf("screen/%08d.png", $this->serial++));
    }
}
