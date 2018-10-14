<?php
namespace Nes\Cpu;

use Nes\Bus\CpuBus;
use Nes\Cpu\Registers\AddrOrDataAndAdditionalCycle;
use Nes\Cpu\Registers\Registers;
use Nes\Debugger;

class Cpu
{
    /** @var \Nes\Cpu\Registers\Registers */
    public $registers;
    /** @var bool */
    public $hasBranched;
    /** @var \Nes\Bus\CpuBus */
    public $bus;
    /** @var \Nes\Cpu\OpCodeProps[] */
    public $opCodeList;
    /** @var \Nes\Cpu\Interrupts */
    public $interrupts;

    const CPU_CLOCK = 1789772.5;

    public function __construct(CpuBus $bus, Interrupts $interrupts)
    {
        $this->bus = $bus;
        $this->interrupts = $interrupts;

        $this->registers = Registers::getDefault();
        $this->hasBranched = false;
        $this->opCodeList = [];

        $opCodes = OpCode::getOpCodes();
        foreach ($opCodes as $key => $op) {
            $this->opCodeList[hexdec($key)] = $op;
        }
    }


    public function reset()
    {
        $this->registers = Registers::getDefault();
        // TODO: flownes set 0x8000 to PC when read(0xfffc) fails.
        $this->registers->pc = $this->read(0xFFFC, "Word");
        printf("Initial pc: %04x\n", $this->registers->pc);
    }

    /**
     * @param \Nes\Cpu\Addressing $mode
     *
     * @return \Nes\Cpu\Registers\AddrOrDataAndAdditionalCycle
     * @throws \Exception
     */
    public function getAddrOrDataWithAdditionalCycle(Addressing $mode): AddrOrDataAndAdditionalCycle
    {
        switch ($mode) {
            case Addressing::Accumulator:
                return new AddrOrDataAndAdditionalCycle(0x00, 0);
            case Addressing::Implied:
                return new AddrOrDataAndAdditionalCycle(0x00, 0);
            case Addressing::Immediate:
                return new AddrOrDataAndAdditionalCycle($this->fetch($this->registers->pc), 0);
            case Addressing::Relative:
                $baseAddr = $this->fetch($this->registers->pc);
                $addr = $baseAddr < 0x80 ? $baseAddr + $this->registers->pc : $baseAddr + $this->registers->pc - 256;
                return new AddrOrDataAndAdditionalCycle(
                    $addr,
                    ($addr & 0xff00) !== ($this->registers->pc & 0xFF00) ? 1 : 0
                );
            case Addressing::ZeroPage:
                return new AddrOrDataAndAdditionalCycle($this->fetch($this->registers->pc), 0);
            case Addressing::ZeroPageX:
                $addr = $this->fetch($this->registers->pc);
                return new AddrOrDataAndAdditionalCycle(
                    ($addr + $this->registers->x) & 0xff,
                    0
                );
            case Addressing::ZeroPageY:
                $addr = $this->fetch($this->registers->pc);
                return new AddrOrDataAndAdditionalCycle(($addr + $this->registers->y & 0xff), 0);
            case Addressing::Absolute:
                return new AddrOrDataAndAdditionalCycle(($this->fetch($this->registers->pc, "Word")), 0);
            case Addressing::AbsoluteX:
                $addr = ($this->fetch($this->registers->pc, "Word"));
                $additionalCycle = ($addr & 0xFF00) !== (($addr + $this->registers->x) & 0xFF00) ? 1 : 0;
                return new AddrOrDataAndAdditionalCycle(($addr + $this->registers->x) & 0xFFFF, $additionalCycle);
            case Addressing::AbsoluteY:
                $addr = ($this->fetch($this->registers->pc, "Word"));
                $additionalCycle = ($addr & 0xFF00) !== (($addr + $this->registers->y) & 0xFF00) ? 1 : 0;
                return new AddrOrDataAndAdditionalCycle(($addr + $this->registers->y) & 0xFFFF, $additionalCycle);
            case Addressing::PreIndexedIndirect:
                $baseAddr = ($this->fetch($this->registers->pc) + $this->registers->x) & 0xFF;
                $addr = $this->read($baseAddr) + ($this->read(($baseAddr + 1) & 0xFF) << 8);
                return new AddrOrDataAndAdditionalCycle(
                    $addr & 0xFFFF,
                    ($addr & 0xFF00) !== ($baseAddr & 0xFF00) ? 1 : 0
                );
            case Addressing::PostIndexedIndirect:
                $addrOrData = $this->fetch($this->registers->pc);
                $baseAddr = $this->read($addrOrData) + ($this->read(($addrOrData + 1) & 0xFF) << 8);
                $addr = $baseAddr + $this->registers->y;
                return new AddrOrDataAndAdditionalCycle(
                    $addr & 0xFFFF,
                    ($addr & 0xFF00) !== ($baseAddr & 0xFF00) ? 1 : 0
                );
            case Addressing::IndirectAbsolute:
                $addrOrData = $this->fetch($this->registers->pc, "Word");
                $addr = $this->read($addrOrData) +
                    ($this->read(($addrOrData & 0xFF00) | ((($addrOrData & 0xFF) + 1) & 0xFF)) << 8);
                return new AddrOrDataAndAdditionalCycle($addr & 0xFFFF, 0);
            default:
                echo($mode);
                throw new \Exception(`Unknown addressing $mode detected.`);
        }
    }

    public function fetch(int $addr, string $size = 'Byte'): int
    {
        $this->registers->pc += ($size === "Word") ? 2 : 1;

        return $this->read($addr, $size);
    }

    public function read(int $addr, string $size = null): int
    {
        $addr &= 0xFFFF;

        return $size === "Word"
            ? ($this->bus->readByCpu($addr) | $this->bus->readByCpu($addr + 1) << 8)
            : $this->bus->readByCpu($addr);
    }

    public function write(int $addr, int $data)
    {
        $this->bus->writeByCpu($addr, $data);
    }

    public function push(int $data)
    {
        $this->write(0x100 | ($this->registers->sp & 0xFF), $data);
        $this->registers->sp--;
    }

    public function pop(): int
    {
        $this->registers->sp++;

        return $this->read(0x100 | ($this->registers->sp & 0xFF), 'Byte');
    }

    public function branch(int $addr)
    {
        $this->registers->pc = $addr;
        $this->hasBranched = true;
    }

    public function pushStatus()
    {
        $status = (+$this->registers->p->negative) << 7 |
            (+$this->registers->p->overflow) << 6 |
            (+$this->registers->p->reserved) << 5 |
            (+$this->registers->p->break_mode) << 4 |
            (+$this->registers->p->decimal_mode) << 3 |
            (+$this->registers->p->interrupt) << 2 |
            (+$this->registers->p->zero) << 1 |
            (+$this->registers->p->carry);
        $this->push($status);
    }

    public function popStatus()
    {
        $status = $this->pop();
        $this->registers->p->negative = !!($status & 0x80);
        $this->registers->p->overflow = !!($status & 0x40);
        $this->registers->p->reserved = !!($status & 0x20);
        $this->registers->p->break_mode = !!($status & 0x10);
        $this->registers->p->decimal_mode = !!($status & 0x08);
        $this->registers->p->interrupt = !!($status & 0x04);
        $this->registers->p->zero = !!($status & 0x02);
        $this->registers->p->carry = !!($status & 0x01);
    }

    public function popPC()
    {
        $this->registers->pc = $this->pop();
        $this->registers->pc += ($this->pop() << 8);
    }

    /**
     * @param string $baseName
     * @param int $addrOrData
     * @param \Nes\Cpu\Addressing $mode
     *
     * @throws \Exception
     */
    public function execInstruction(string $baseName, int $addrOrData, Addressing $mode)
    {
        $this->hasBranched = false;
        switch ($baseName) {
            case 'LDA':
                $this->registers->a = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $this->registers->p->negative = !!($this->registers->a & 0x80);
                $this->registers->p->zero = !$this->registers->a;
                break;
            case 'LDX':
                $this->registers->x = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $this->registers->p->negative = !!($this->registers->x & 0x80);
                $this->registers->p->zero = !$this->registers->x;
                break;
            case 'LDY':
                $this->registers->y = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $this->registers->p->negative = !!($this->registers->y & 0x80);
                $this->registers->p->zero = !$this->registers->y;
                break;
            case 'STA':
                $this->write($addrOrData, $this->registers->a);
                break;
            case 'STX':
                $this->write($addrOrData, $this->registers->x);
                break;
            case 'STY':
                $this->write($addrOrData, $this->registers->y);
                break;
            case 'TAX':
                $this->registers->x = $this->registers->a;
                $this->registers->p->negative = !!($this->registers->x & 0x80);
                $this->registers->p->zero = !$this->registers->x;
                break;
            case 'TAY':
                $this->registers->y = $this->registers->a;
                $this->registers->p->negative = !!($this->registers->y & 0x80);
                $this->registers->p->zero = !$this->registers->y;
                break;
            case 'TSX':
                $this->registers->x = $this->registers->sp & 0xFF;
                $this->registers->p->negative = !!($this->registers->x & 0x80);
                $this->registers->p->zero = !$this->registers->x;
                break;
            case 'TXA':
                $this->registers->a = $this->registers->x;
                $this->registers->p->negative = !!($this->registers->a & 0x80);
                $this->registers->p->zero = !$this->registers->a;
                break;
            case 'TXS':
                $this->registers->sp = $this->registers->x + 0x0100;
                break;
            case 'TYA':
                $this->registers->a = $this->registers->y;
                $this->registers->p->negative = !!($this->registers->a & 0x80);
                $this->registers->p->zero = !$this->registers->a;
                break;
            case 'ADC':
                $data = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $operated = $data + $this->registers->a + $this->registers->p->carry;
                $overflow = (!((($this->registers->a ^ $data) & 0x80) != 0) &&
                    ((($this->registers->a ^ $operated) & 0x80)) != 0);
                $this->registers->p->overflow = $overflow;
                $this->registers->p->carry = $operated > 0xFF;
                $this->registers->p->negative = !!($operated & 0x80);
                $this->registers->p->zero = !($operated & 0xFF);
                $this->registers->a = $operated & 0xFF;
                break;
            case 'AND':
                $data = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $operated = $data & $this->registers->a;
                $this->registers->p->negative = !!($operated & 0x80);
                $this->registers->p->zero = !$operated;
                $this->registers->a = $operated & 0xFF;
                break;
            case 'ASL':
                if ($mode == 'accumulator') {
                    $acc = $this->registers->a;
                    $this->registers->p->carry = !!($acc & 0x80);
                    $this->registers->a = ($acc << 1) & 0xFF;
                    $this->registers->p->zero = !$this->registers->a;
                    $this->registers->p->negative = !!($this->registers->a & 0x80);
                } else {
                    $data = $this->read($addrOrData);
                    $this->registers->p->carry = !!($data & 0x80);
                    $shifted = ($data << 1) & 0xFF;
                    $this->write($addrOrData, $shifted);
                    $this->registers->p->zero = !$shifted;
                    $this->registers->p->negative = !!($shifted & 0x80);
                }
                break;
            case 'BIT':
                $data = $this->read($addrOrData);
                $this->registers->p->negative = !!($data & 0x80);
                $this->registers->p->overflow = !!($data & 0x40);
                $this->registers->p->zero = !($this->registers->a & $data);
                break;
            case 'CMP':
                $data = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $compared = $this->registers->a - $data;
                $this->registers->p->carry = $compared >= 0;
                $this->registers->p->negative = !!($compared & 0x80);
                $this->registers->p->zero = !($compared & 0xff);
                break;
            case 'CPX':
                $data = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $compared = $this->registers->x - $data;
                $this->registers->p->carry = $compared >= 0;
                $this->registers->p->negative = !!($compared & 0x80);
                $this->registers->p->zero = !($compared & 0xff);
                break;
            case 'CPY':
                $data = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $compared = $this->registers->y - $data;
                $this->registers->p->carry = $compared >= 0;
                $this->registers->p->negative = !!($compared & 0x80);
                $this->registers->p->zero = !($compared & 0xff);
                break;
            case 'DEC':
                $data = ($this->read($addrOrData) - 1) & 0xFF;
                $this->registers->p->negative = !!($data & 0x80);
                $this->registers->p->zero = !$data;
                $this->write($addrOrData, $data);
                break;
            case 'DEX':
                $this->registers->x = ($this->registers->x - 1) & 0xFF;
                $this->registers->p->negative = !!($this->registers->x & 0x80);
                $this->registers->p->zero = !$this->registers->x;
                break;
            case 'DEY':
                $this->registers->y = ($this->registers->y - 1) & 0xFF;
                $this->registers->p->negative = !!($this->registers->y & 0x80);
                $this->registers->p->zero = !$this->registers->y;
                break;
            case 'EOR':
                $data = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $operated = $data ^ $this->registers->a;
                $this->registers->p->negative = !!($operated & 0x80);
                $this->registers->p->zero = !$operated;
                $this->registers->a = $operated & 0xFF;
                break;
            case 'INC':
                $data = ($this->read($addrOrData) + 1) & 0xFF;
                $this->registers->p->negative = !!($data & 0x80);
                $this->registers->p->zero = !$data;
                $this->write($addrOrData, $data);
                break;
            case 'INX':
                $this->registers->x = ($this->registers->x + 1) & 0xFF;
                $this->registers->p->negative = !!($this->registers->x & 0x80);
                $this->registers->p->zero = !$this->registers->x;
                break;
            case 'INY':
                $this->registers->y = ($this->registers->y + 1) & 0xFF;
                $this->registers->p->negative = !!($this->registers->y & 0x80);
                $this->registers->p->zero = !$this->registers->y;
                break;
            case 'LSR':
                if ($mode == 'accumulator') {
                    $acc = $this->registers->a & 0xFF;
                    $this->registers->p->carry = !!($acc & 0x01);
                    $this->registers->a = $acc >> 1;
                    $this->registers->p->zero = !$this->registers->a;
                } else {
                    $data = $this->read($addrOrData);
                    $this->registers->p->carry = !!($data & 0x01);
                    $this->registers->p->zero = !($data >> 1);
                    $this->write($addrOrData, $data >> 1);
                }
                $this->registers->p->negative = false;
                break;
            case 'ORA':
                $data = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $operated = $data | $this->registers->a;
                $this->registers->p->negative = !!($operated & 0x80);
                $this->registers->p->zero = !$operated;
                $this->registers->a = $operated & 0xFF;
                break;
            case 'ROL':
                if ($mode == 'accumulator') {
                    $acc = $this->registers->a;
                    $this->registers->a = ($acc << 1) & 0xFF | ($this->registers->p->carry ? 0x01 : 0x00);
                    $this->registers->p->carry = !!($acc & 0x80);
                    $this->registers->p->zero = !$this->registers->a;
                    $this->registers->p->negative = !!($this->registers->a & 0x80);
                } else {
                    $data = $this->read($addrOrData);
                    $writeData = ($data << 1 | ($this->registers->p->carry ? 0x01 : 0x00)) & 0xFF;
                    $this->write($addrOrData, $writeData);
                    $this->registers->p->carry = !!($data & 0x80);
                    $this->registers->p->zero = !$writeData;
                    $this->registers->p->negative = !!($writeData & 0x80);
                }
                break;
            case 'ROR':
                if ($mode == 'accumulator') {
                    $acc = $this->registers->a;
                    $this->registers->a = $acc >> 1 | ($this->registers->p->carry ? 0x80 : 0x00);
                    $this->registers->p->carry = !!($acc & 0x01);
                    $this->registers->p->zero = !$this->registers->a;
                    $this->registers->p->negative = !!($this->registers->a & 0x80);
                } else {
                    $data = $this->read($addrOrData);
                    $writeData = $data >> 1 | ($this->registers->p->carry ? 0x80 : 0x00);
                    $this->write($addrOrData, $writeData);
                    $this->registers->p->carry = !!($data & 0x01);
                    $this->registers->p->zero = !$writeData;
                    $this->registers->p->negative = !!($writeData & 0x80);
                }
                break;
            case 'SBC':
                $data = ($mode == 'immediate') ? $addrOrData : $this->read($addrOrData);
                $operated = $this->registers->a - $data - ($this->registers->p->carry ? 0 : 1);
                $overflow = ((($this->registers->a ^ $operated) & 0x80) != 0 &&
                    (($this->registers->a ^ $data) & 0x80) != 0);
                $this->registers->p->overflow = $overflow;
                $this->registers->p->carry = $operated >= 0;
                $this->registers->p->negative = !!($operated & 0x80);
                $this->registers->p->zero = !($operated & 0xFF);
                $this->registers->a = $operated & 0xFF;
                break;
            case 'PHA':
                $this->push($this->registers->a);
                break;
            case 'PHP':
                $this->registers->p->break_mode = true;
                $this->pushStatus();
                break;
            case 'PLA':
                $this->registers->a = $this->pop();
                $this->registers->p->negative = !!($this->registers->a & 0x80);
                $this->registers->p->zero = !$this->registers->a;
                break;
            case 'PLP':
                $this->popStatus();
                $this->registers->p->reserved = true;
                break;
            case 'JMP':
                $this->registers->pc = $addrOrData;
                break;
            case 'JSR':
                $pc = $this->registers->pc - 1;
                $this->push(($pc >> 8) & 0xFF);
                $this->push($pc & 0xFF);
                $this->registers->pc = $addrOrData;
                break;
            case 'RTS':
                $this->popPC();
                $this->registers->pc++;
                break;
            case 'RTI':
                $this->popStatus();
                $this->popPC();
                $this->registers->p->reserved = true;
                break;
            case 'BCC':
                if (!$this->registers->p->carry) {
                    $this->branch($addrOrData);
                }
                break;
            case 'BCS':
                if ($this->registers->p->carry) {
                    $this->branch($addrOrData);
                }
                break;
            case 'BEQ':
                if ($this->registers->p->zero) {
                    $this->branch($addrOrData);
                }
                break;
            case 'BMI':
                if ($this->registers->p->negative) {
                    $this->branch($addrOrData);
                }
                break;
            case 'BNE':
                if (!$this->registers->p->zero) {
                    $this->branch($addrOrData);
                }
                break;
            case 'BPL':
                if (!$this->registers->p->negative) {
                    $this->branch($addrOrData);
                }
                break;
            case 'BVS':
                if ($this->registers->p->overflow) {
                    $this->branch($addrOrData);
                }
                break;
            case 'BVC':
                if (!$this->registers->p->overflow) {
                    $this->branch($addrOrData);
                }
                break;
            case 'CLD':
                $this->registers->p->decimal_mode = false;
                break;
            case 'CLC':
                $this->registers->p->carry = false;
                break;
            case 'CLI':
                $this->registers->p->interrupt = false;
                break;
            case 'CLV':
                $this->registers->p->overflow = false;
                break;
            case 'SEC':
                $this->registers->p->carry = true;
                break;
            case 'SEI':
                $this->registers->p->interrupt = true;
                break;
            case 'SED':
                $this->registers->p->decimal_mode = true;
                break;
            case 'BRK':
                $interrupt = $this->registers->p->interrupt;
                $this->registers->pc++;
                $this->push(($this->registers->pc >> 8) & 0xFF);
                $this->push($this->registers->pc & 0xFF);
                $this->registers->p->break_mode = true;
                $this->pushStatus();
                $this->registers->p->interrupt = true;
                // Ignore interrupt when already set.
                if (!$interrupt) {
                    $this->registers->pc = $this->read(0xFFFE, "Word");
                }
                $this->registers->pc--;
                break;
            case 'NOP':
                break;
            // Unofficial Opecode
            case 'NOPD':
                $this->registers->pc++;
                break;
            case 'NOPI':
                $this->registers->pc += 2;
                break;
            case 'LAX':
                $this->registers->a = $this->registers->x = $this->read($addrOrData);
                $this->registers->p->negative = !!($this->registers->a & 0x80);
                $this->registers->p->zero = !$this->registers->a;
                break;
            case 'SAX':
                $operated = $this->registers->a & $this->registers->x;
                $this->write($addrOrData, $operated);
                break;
            case 'DCP':
                $operated = ($this->read($addrOrData) - 1) & 0xFF;
                $this->registers->p->negative = !!((($this->registers->a - $operated) & 0x1FF) & 0x80);
                $this->registers->p->zero = !(($this->registers->a - $operated) & 0x1FF);
                $this->write($addrOrData, $operated);
                break;
            case 'ISB':
                $data = ($this->read($addrOrData) + 1) & 0xFF;
                $operated = (~$data & 0xFF) + $this->registers->a + $this->registers->p->carry;
                $overflow = (!((($this->registers->a ^ $data) & 0x80) != 0) &&
                    ((($this->registers->a ^ $operated) & 0x80)) != 0);
                $this->registers->p->overflow = $overflow;
                $this->registers->p->carry = $operated > 0xFF;
                $this->registers->p->negative = !!($operated & 0x80);
                $this->registers->p->zero = !($operated & 0xFF);
                $this->registers->a = $operated & 0xFF;
                $this->write($addrOrData, $data);
                break;
            case 'SLO':
                $data = $this->read($addrOrData);
                $this->registers->p->carry = !!($data & 0x80);
                $data = ($data << 1) & 0xFF;
                $this->registers->a |= $data;
                $this->registers->p->negative = !!($this->registers->a & 0x80);
                $this->registers->p->zero = !($this->registers->a & 0xFF);
                $this->write($addrOrData, $data);
                break;
            case 'RLA':
                $data = ($this->read($addrOrData) << 1) + $this->registers->p->carry;
                $this->registers->p->carry = !!($data & 0x100);
                $this->registers->a = ($data & $this->registers->a) & 0xFF;
                $this->registers->p->negative = !!($this->registers->a & 0x80);
                $this->registers->p->zero = !($this->registers->a & 0xFF);
                $this->write($addrOrData, $data);
                break;
            case 'SRE':
                $data = $this->read($addrOrData);
                $this->registers->p->carry = !!($data & 0x01);
                $data >>= 1;
                $this->registers->a ^= $data;
                $this->registers->p->negative = !!($this->registers->a & 0x80);
                $this->registers->p->zero = !($this->registers->a & 0xFF);
                $this->write($addrOrData, $data);
                break;
            case 'RRA':
                $data = $this->read($addrOrData);
                $carry = !!($data & 0x01);
                $data = ($data >> 1) | ($this->registers->p->carry ? 0x80 : 0x00);
                $operated = $data + $this->registers->a + $carry;
                $overflow = (!((($this->registers->a ^ $data) & 0x80) != 0) &&
                    ((($this->registers->a ^ $operated) & 0x80)) != 0);
                $this->registers->p->overflow = $overflow;
                $this->registers->p->negative = !!($operated & 0x80);
                $this->registers->p->zero = !($operated & 0xFF);
                $this->registers->a = $operated & 0xFF;
                $this->registers->p->carry = $operated > 0xFF;
                $this->write($addrOrData, $data);
                break;

            default:
                throw new \Exception(sprintf('Unknown opecode %s detected.', $baseName));
        }
    }

    public function processNmi()
    {
        $this->interrupts->deassertNmi();
        $this->registers->p->break_mode = false;
        $this->push(($this->registers->pc >> 8) & 0xFF);
        $this->push($this->registers->pc & 0xFF);
        $this->pushStatus();
        $this->registers->p->interrupt = true;
        $this->registers->pc = $this->read(0xFFFA, "Word");
    }

    public function processIrq()
    {
        if ($this->registers->p->interrupt) {
            return;
        }
        $this->interrupts->deassertIrq();
        $this->registers->p->break_mode = false;
        $this->push(($this->registers->pc >> 8) & 0xFF);
        $this->push($this->registers->pc & 0xFF);
        $this->pushStatus();
        $this->registers->p->interrupt = true;
        $this->registers->pc = $this->read(0xFFFE, "Word");
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function run(): int
    {
        if ($this->interrupts->isNmiAssert()) {
            $this->processNmi();
        }
        if ($this->interrupts->isIrqAssert()) {
            $this->processIrq();
        }
        $opcode = $this->fetch($this->registers->pc, 'Byte');
        //printf("pc: 0x%04x  data: 0x%02x\n", $this->registers->pc, $opcode);
        //if (!isset($this->opCodeList[$opcode])) { $this->debug($opcode); }
        $ocp = $this->opCodeList[$opcode];
        $data = $this->getAddrOrDataWithAdditionalCycle($ocp->mode);
        $this->execInstruction($ocp->baseName, $data->addrOrData, $ocp->mode);

        return $ocp->cycle + $data->additionalCycle + ($this->hasBranched ? 1 : 0);
    }

    private function debug($opcode)
    {
        printf(
            "Invalid opcode: %s in pc: %04x\n",
            dechex($opcode),
            $this->registers->pc
        );
        if ($this->registers->pc < 0x0800) {
            Debugger::dump($this->bus->ram->ram);
        } else {
            if ($this->registers->pc < 0x2000) {
                printf("Redirect ram: %04x\n", $this->registers->pc - 0x0800);
                Debugger::dump($this->bus->ram->ram);
            } else {
                if ($this->registers->pc < 0x4000) {
                    printf("Ppu: %04x\n", ($this->registers->pc - 0x2000) % 8);
                    Debugger::dump($this->bus->ppu->registers);
                } else {
                    if ($this->registers->pc === 0x4016) {
                        printf("Keypad\n");
                    } else {
                        if ($this->registers->pc >= 0xC000) {
                            if ($this->bus->programRom->size() <= 0x4000) {
                                printf("Redirect program rom: %04x\n", $this->registers->pc - 0xC000);
                                Debugger::dump($this->bus->programRom->rom);
                            } else {
                                printf("Redirect program rom: %04x\n", $this->registers->pc - 0x8000);
                                Debugger::dump($this->bus->programRom->rom);
                            }
                        } else {
                            if ($this->registers->pc >= 0x8000) {
                                printf("Redirect program rom: %04x\n", $this->registers->pc - 0x8000);
                                Debugger::dump($this->bus->programRom->rom);
                            } else {
                                printf("Something wrong...\n");
                            }
                        }
                    }
                }
            }
        }
    }
}
