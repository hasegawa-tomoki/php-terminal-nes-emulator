<?php
namespace Nes\Bus;

class PpuBus
{
    /** @var \Nes\Bus\Ram */
    public $characterRam;

    public function __construct(Ram $characterRam)
    {
        $this->characterRam = $characterRam;
    }

    public function readByPpu(int $addr): int
    {
        return $this->characterRam->read($addr);
    }

    public function writeByPpu(int $addr, int $data)
    {
        $this->characterRam->write($addr, $data);
    }
}
