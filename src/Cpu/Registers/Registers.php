<?php
namespace Nes\Cpu\Registers;

class Registers
{
    /** @var int byte */
    public $a;
    /** @var int byte */
    public $x;
    /** @var int byte */
    public $y;
    /** @var \Nes\Cpu\Registers\Status */
    public $p;
    /** @var int word */
    public $sp;
    /** @var int word */
    public $pc;

    public static function getDefault()
    {
        $instance = new self;
        $instance->a = 0x00;
        $instance->x = 0x00;
        $instance->y = 0x00;
        $instance->p = new Status(
            false,
            false,
            true,
            true,
            false,
            true,
            false,
            false
        );
        $instance->sp = 0x01fd;
        $instance->pc = 0x0000;

        return $instance;
    }
}
