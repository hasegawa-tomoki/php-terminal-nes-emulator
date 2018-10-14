<?php
namespace Nes\Cpu;

class OpCodeProps
{
    public $fullName;
    public $baseName;
    public $mode;
    public $cycle;

    public function __construct(string $fullName, string $baseName, Addressing $mode, int $cycle)
    {
        $this->fullName = $fullName;
        $this->baseName = $baseName;
        $this->mode = $mode;
        $this->cycle = $cycle;
    }
}
