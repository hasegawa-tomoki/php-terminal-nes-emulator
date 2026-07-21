<?php
namespace Nes\Bus;

use Nes\Cpu\Dma;
use Nes\Debugger;
use Nes\Mapper\MapperInterface;
use Nes\Ppu\Ppu;

class CpuBus
{
    public $ram;
    public $mapper;
    public $ppu;
    public $keypad;
    public $dma;

    public function __construct(Ram $ram, MapperInterface $mapper, Ppu $ppu, Keypad $keypad, Dma $dma)
    {
        $this->ram = $ram;
        $this->mapper = $mapper;
        $this->ppu = $ppu;
        $this->keypad = $keypad;
        $this->dma = $dma;
    }

    public function readByCpu($addr): int
    {
        if ($addr < 0x0800) {
            return $this->ram->read($addr);
        } elseif ($addr < 0x2000) {
            // mirror
            return $this->ram->read($addr - 0x0800);
        } elseif ($addr < 0x4000) {
            // mirror
            $data = $this->ppu->read(($addr - 0x2000) % 8);
            return $data;
        } elseif ($addr === 0x4016) {
            // TODO Add 2P
            return $this->keypad->read();
        } elseif ($addr >= 0x8000) {
            // ROM
            return $this->mapper->readPrg($addr);
        } elseif ($addr >= 0x6000) {
            // battery backup RAM
            return $this->mapper->readPrgRam($addr);
        }
        return false;
    }

    public function writeByCpu(int $addr, int $data)
    {
        if ($addr < 0x0800) {
            // RAM
            $this->ram->write($addr, $data);
        } elseif ($addr < 0x2000) {
            // mirror
            $this->ram->write($addr - 0x0800, $data);
        } elseif ($addr < 0x2008) {
            // PPU
            $this->ppu->write($addr - 0x2000, $data);
        } elseif ($addr >= 0x4000 && $addr < 0x4020) {
            if ($addr === 0x4014) {
                $this->dma->write($data);
            } elseif ($addr === 0x4016) {
                // TODO Add 2P
                $this->keypad->write($data);
            } else {
                // APU
                return false;
            }
        } elseif ($addr >= 0x6000 && $addr < 0x8000) {
            // battery backup RAM
            $this->mapper->writePrgRam($addr, $data);
        } elseif ($addr >= 0x8000) {
            // mapper register
            $this->mapper->writePrg($addr, $data);
        }
    }
}
