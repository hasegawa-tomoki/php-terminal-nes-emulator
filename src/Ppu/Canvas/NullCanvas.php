<?php
namespace Nes\Ppu\Canvas;

class NullCanvas implements CanvasInterface
{
    private $fp;
    private $frame;
    private $last;
    private $initial;

    public function __construct()
    {
        $dir = 'tmp';
        if (! is_dir(($dir))) {
            mkdir($dir);
        }
        $this->fp = fopen($dir.'/nes.log', 'w');

        $this->last = -1;
        $this->initial = time();
    }

    public function __destruct()
    {
        fclose($this->fp);
    }

    public function draw(array $frameBuffer)
    {
        $microTime = microtime(true);
        $second = floor($microTime);
        if ($second !== $this->last) {
            printf("%6d %dfps\n", $second - $this->initial, $this->frame);
            $this->frame = 0;
        }
        fprintf($this->fp, "%-8.2f frame %d\n", $microTime, $this->frame++);

        $this->last = floor($microTime);
    }
}
