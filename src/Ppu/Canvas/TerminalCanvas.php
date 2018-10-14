<?php
namespace Nes\Ppu\Canvas;

class TerminalCanvas implements CanvasInterface
{
    protected $brailleCharOffset;
    protected $canvas;

    protected $currentSecond = 0;
    protected $framesInSecond = 0;
    protected $fps = 0;
    protected $height = 0;
    protected $lastFrame;
    protected $lastFrameCanvasBuffer;
    /**
     * Braille Pixel Matrix
     *   ,___,
     *   |1 4|
     *   |2 5|
     *   |3 6|
     *   |7 8|
     *   `````
     * @var array
     */
    protected $pixelMap;
    protected $width = 0;

    public $threshold = 127;
    public $frameSkip = 0;

    public function __construct()
    {
        $this->brailleCharOffset = html_entity_decode('&#' . (0x2800) . ';', ENT_NOQUOTES, 'UTF-8');
        $this->pixelMap = [
            [
                html_entity_decode('&#' . (0x2801) . ';', ENT_NOQUOTES, 'UTF-8'),
                html_entity_decode('&#' . (0x2808) . ';', ENT_NOQUOTES, 'UTF-8')
            ],
            [
                html_entity_decode('&#' . (0x2802) . ';', ENT_NOQUOTES, 'UTF-8'),
                html_entity_decode('&#' . (0x2810) . ';', ENT_NOQUOTES, 'UTF-8')
            ],
            [
                html_entity_decode('&#' . (0x2804) . ';', ENT_NOQUOTES, 'UTF-8'),
                html_entity_decode('&#' . (0x2820) . ';', ENT_NOQUOTES, 'UTF-8')
            ],
            [
                html_entity_decode('&#' . (0x2840) . ';', ENT_NOQUOTES, 'UTF-8'),
                html_entity_decode('&#' . (0x2880) . ';', ENT_NOQUOTES, 'UTF-8')
            ],
        ];
    }

    public function draw(array $canvasBuffer)
    {
        //Calculate current FPS
        if ($this->currentSecond != time()) {
            $this->fps = $this->framesInSecond;
            $this->currentSecond = time();
            $this->framesInSecond = 1;
        } else {
            ++$this->framesInSecond;
        }

        $screenWidth = 256;
        $screenHeight = 224;
        $charWidth = $screenWidth / 2;
        $charHeight = $screenHeight / 4;

        if ($canvasBuffer != $this->lastFrameCanvasBuffer) {
            $chars = array_fill(0, $screenWidth * $screenHeight, $this->brailleCharOffset);

            $frame = '';
            for ($y = 0; $y < $screenHeight; $y++) {
                for ($x = 0; $x < $screenWidth; $x++) {
                    $pixelCanvasNumber = ($x + ($screenWidth * $y)) * 4;
                    $charPosition = floor($x / 2) + (floor($y / 4) * $charWidth);

                    $pixelAvg = (
                        $canvasBuffer[$pixelCanvasNumber] +
                        $canvasBuffer[$pixelCanvasNumber + 1] +
                        $canvasBuffer[$pixelCanvasNumber + 2]
                        ) / 3;
                    if ($pixelAvg > $this->threshold) {
                        $chars[$charPosition] |= $this->pixelMap[$y % 4][$x % 2];
                    }

                    if ($x % 2 === 1 && $y % 4 === 3) {
                        $frame .= $chars[$charPosition];

                        if ($x % ($screenWidth - 1) === 0) {
                            $frame .= PHP_EOL;
                        }
                    }
                }
            }

            $this->lastFrame = $frame;
            $this->lastFrameCanvasBuffer = $canvasBuffer;

            $content = "\e[H\e[2J";

            if ($this->height > 0 && $this->width > 0) {
                $content = "\e[{$this->height}A\e[{$this->width}D";
            }

            $content .= sprintf('FPS: %3d - Frame Skip: %3d' . PHP_EOL, $this->fps, $this->framesInSecond) . $frame;
            echo $content;

            $this->height = $charHeight + 1;
            $this->width = $charWidth;
        }
    }
}
