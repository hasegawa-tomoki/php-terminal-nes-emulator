<?php
namespace Nes\NesFile;

class NesRom
{
    /** @var bool */
    public $isHorizontalMirror;
    /** @var int[] */
    public $programRom;
    /** @var int[] */
    public $characterRom;

    public function __construct(bool $isHorizontalMirror, array $programRom, array $characterRom)
    {
        $this->isHorizontalMirror = $isHorizontalMirror;
        $this->programRom = $programRom;
        $this->characterRom = $characterRom;
    }
}
