<?php
namespace Nes\Mapper;

use Nes\Ppu\Ppu;

interface MapperInterface
{
    /**
     * Read from program ROM. $addr is a CPU address (0x8000 - 0xFFFF).
     */
    public function readPrg(int $addr): int;

    /**
     * Write to the mapper via program ROM address space (0x8000 - 0xFFFF).
     */
    public function writePrg(int $addr, int $data);

    /**
     * Read from character ROM/RAM. $addr is a PPU address (0x0000 - 0x1FFF).
     */
    public function readChr(int $addr): int;

    /**
     * Write to character RAM. $addr is a PPU address (0x0000 - 0x1FFF).
     */
    public function writeChr(int $addr, int $data);

    /**
     * Read from PRG-RAM (battery backup RAM). $addr is a CPU address (0x6000 - 0x7FFF).
     */
    public function readPrgRam(int $addr): int;

    /**
     * Write to PRG-RAM. $addr is a CPU address (0x6000 - 0x7FFF).
     */
    public function writePrgRam(int $addr, int $data);

    /**
     * Give the mapper access to the PPU so it can control mirroring.
     */
    public function setPpu(Ppu $ppu);
}
