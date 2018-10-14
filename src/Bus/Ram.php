<?php
namespace Nes\Bus;

class Ram
{
    /** @var int[] */
    public $ram = [];

    public function __construct(int $size)
    {
        $this->ram = array_fill(0, $size, 0);
    }

    public function reset()
    {
        $this->ram = array_fill(0, count($this->ram) - 1, 0);
    }

    public function read(int $addr)
    {
        return $this->ram[$addr];
    }

    public function write(int $addr, int $val)
    {
        $this->ram[$addr] = $val;
    }
}
