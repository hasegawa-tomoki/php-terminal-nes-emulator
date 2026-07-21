<?php
namespace Nes\NesFile;

class NesRom
{
    /** @var bool */
    public $isHorizontalMirror;
    /** @var int */
    public $mapper;
    /** @var int[] */
    public $programRom;
    /** @var int[] */
    public $characterRom;

    public function __construct(bool $isHorizontalMirror, int $mapper, array $programRom, array $characterRom)
    {
        $this->isHorizontalMirror = $isHorizontalMirror;
        $this->mapper = $mapper;
        $this->programRom = $programRom;
        $this->characterRom = $characterRom;
    }
}
