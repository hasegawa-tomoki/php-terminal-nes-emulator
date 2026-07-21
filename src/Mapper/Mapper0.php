<?php
namespace Nes\Mapper;

use Nes\NesFile\NesRom;
use Nes\Ppu\Ppu;

class Mapper0 implements MapperInterface
{
    /** @var int[] */
    public $programRom;
    /** @var int[] Character ROM, or character RAM when the cartridge has none */
    public $characterRam;
    /** @var int[] */
    public $programRam;

    public function __construct(NesRom $nesRom)
    {
        $this->programRom = $nesRom->programRom;
        if (count($nesRom->characterRom) > 0) {
            $this->characterRam = $nesRom->characterRom;
        } else {
            $this->characterRam = array_fill(0, 0x2000, 0);
        }
        $this->programRam = array_fill(0, 0x2000, 0);
    }

    public function readPrg(int $addr): int
    {
        // 16KB roms are mirrored into 0xC000 - 0xFFFF
        $offset = (count($this->programRom) <= 0x4000) ? $addr & 0x3FFF : $addr & 0x7FFF;
        return $this->programRom[$offset];
    }

    public function writePrg(int $addr, int $data)
    {
    }

    public function readChr(int $addr): int
    {
        return $this->characterRam[$addr] ?? 0;
    }

    public function writeChr(int $addr, int $data)
    {
        $this->characterRam[$addr] = $data;
    }

    public function readPrgRam(int $addr): int
    {
        return $this->programRam[$addr - 0x6000];
    }

    public function writePrgRam(int $addr, int $data)
    {
        $this->programRam[$addr - 0x6000] = $data;
    }

    public function setPpu(Ppu $ppu)
    {
    }
}
