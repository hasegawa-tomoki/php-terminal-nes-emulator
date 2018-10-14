<?php
namespace Nes\Cpu\Registers;

class Status
{
    public $negative;
    public $overflow;
    public $reserved;
    public $break_mode;
    public $decimal_mode;
    public $interrupt;
    public $zero;
    public $carry;
    
    public function __construct(
        bool $negative,
        bool $overflow,
        bool $reserved,
        bool $break_mode,
        bool $decimal_mode,
        bool $interrupt,
        bool $zero,
        bool $carry
    ) {
        $this->negative = $negative;
        $this->overflow = $overflow;
        $this->reserved = $reserved;
        $this->break_mode = $break_mode;
        $this->decimal_mode = $decimal_mode;
        $this->interrupt = $interrupt;
        $this->zero = $zero;
        $this->carry = $carry;
    }
}
