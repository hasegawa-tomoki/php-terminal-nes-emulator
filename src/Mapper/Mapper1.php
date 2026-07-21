<?php
namespace Nes\Mapper;

use Nes\NesFile\NesRom;
use Nes\Ppu\Ppu;

/**
 * MMC1 (mapper 1).
 * Registers are written through a serial port: 5 writes of 1 bit each
 * (LSB first) to 0x8000 - 0xFFFF. Writing a value with bit 7 set resets
 * the shift register and locks PRG mode to 3.
 */
class Mapper1 implements MapperInterface
{
    /** @var int[] */
    public $programRom;
    /** @var int[] Character ROM, or character RAM when the cartridge has none */
    public $characterRam;
    /** @var int[] 32KB PRG-RAM (SXROM), banked via CHR bank 0 bits 2-3 */
    public $programRam;
    /** @var \Nes\Ppu\Ppu|null */
    public $ppu;

    /** @var int */
    public $shiftRegister = 0x10;
    /** @var int bit 0-1: mirroring, bit 2-3: PRG bank mode, bit 4: CHR bank mode */
    public $control = 0x0C;
    /** @var int */
    public $characterBank0 = 0;
    /** @var int */
    public $characterBank1 = 0;
    /** @var int */
    public $programBank = 0;

    public function __construct(NesRom $nesRom)
    {
        $this->programRom = $nesRom->programRom;
        if (count($nesRom->characterRom) > 0) {
            $this->characterRam = $nesRom->characterRom;
        } else {
            $this->characterRam = array_fill(0, 0x2000, 0);
        }
        $this->programRam = array_fill(0, 0x8000, 0);
    }

    public function setPpu(Ppu $ppu)
    {
        $this->ppu = $ppu;
        $this->updateMirroring();
    }

    public function readPrg(int $addr): int
    {
        $bankCount = intdiv(count($this->programRom), 0x4000);
        $prgMode = ($this->control >> 2) & 0x03;
        if ($prgMode <= 1) {
            // 32KB mode: ignore low bit of bank number
            $bank = ($this->programBank & 0x0E) + (($addr < 0xC000) ? 0 : 1);
        } elseif ($prgMode === 2) {
            // First bank fixed at 0x8000, switchable bank at 0xC000
            $bank = ($addr < 0xC000) ? 0 : $this->programBank;
        } else {
            // Switchable bank at 0x8000, last bank fixed at 0xC000
            $bank = ($addr < 0xC000) ? $this->programBank : $bankCount - 1;
        }
        return $this->programRom[($bank % $bankCount) * 0x4000 + ($addr & 0x3FFF)];
    }

    public function writePrg(int $addr, int $data)
    {
        if ($data & 0x80) {
            $this->shiftRegister = 0x10;
            $this->control |= 0x0C;
            return;
        }
        $isFifthWrite = ($this->shiftRegister & 0x01) === 0x01;
        $this->shiftRegister = ($this->shiftRegister >> 1) | (($data & 0x01) << 4);
        if ($isFifthWrite) {
            $this->writeRegister($addr, $this->shiftRegister);
            $this->shiftRegister = 0x10;
        }
    }

    public function readChr(int $addr): int
    {
        return $this->characterRam[$this->characterOffset($addr)] ?? 0;
    }

    public function writeChr(int $addr, int $data)
    {
        $this->characterRam[$this->characterOffset($addr)] = $data;
    }

    public function readPrgRam(int $addr): int
    {
        return $this->programRam[$this->programRamOffset($addr)];
    }

    public function writePrgRam(int $addr, int $data)
    {
        $this->programRam[$this->programRamOffset($addr)] = $data;
    }

    private function programRamOffset(int $addr): int
    {
        // SXROM: 8KB PRG-RAM bank selected by bits 2-3 of the CHR bank 0 register
        $bank = ($this->characterBank0 >> 2) & 0x03;
        return $bank * 0x2000 + ($addr - 0x6000);
    }

    private function characterOffset(int $addr): int
    {
        if ($this->control & 0x10) {
            // Two switchable 4KB banks
            $bank = ($addr < 0x1000) ? $this->characterBank0 : $this->characterBank1;
            $offset = $bank * 0x1000 + ($addr & 0x0FFF);
        } else {
            // One 8KB bank: ignore low bit of bank number
            $offset = ($this->characterBank0 & 0x1E) * 0x1000 + ($addr & 0x1FFF);
        }
        return $offset % count($this->characterRam);
    }

    private function writeRegister(int $addr, int $value)
    {
        if ($addr < 0xA000) {
            $this->control = $value;
            $this->updateMirroring();
        } elseif ($addr < 0xC000) {
            $this->characterBank0 = $value;
        } elseif ($addr < 0xE000) {
            $this->characterBank1 = $value;
        } else {
            // Bit 4 is the PRG RAM enable bit (ignored)
            $this->programBank = $value & 0x0F;
        }
    }

    private function updateMirroring()
    {
        if (! $this->ppu) {
            return;
        }
        switch ($this->control & 0x03) {
            case 0:
                $this->ppu->setMirroring(Ppu::MIRROR_SINGLE_LOWER);
                break;
            case 1:
                $this->ppu->setMirroring(Ppu::MIRROR_SINGLE_UPPER);
                break;
            case 2:
                $this->ppu->setMirroring(Ppu::MIRROR_VERTICAL);
                break;
            case 3:
                $this->ppu->setMirroring(Ppu::MIRROR_HORIZONTAL);
                break;
        }
    }
}
