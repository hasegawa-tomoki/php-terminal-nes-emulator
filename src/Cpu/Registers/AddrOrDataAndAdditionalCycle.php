<?php
namespace Nes\Cpu\Registers;

class AddrOrDataAndAdditionalCycle
{
    /** @var int */
    public $addrOrData;
    /** @var int */
    public $additionalCycle;

    public function __construct(int $addrOrData, int $additionalCycle)
    {
        $this->addrOrData = $addrOrData;
        $this->additionalCycle = $additionalCycle;
    }
}
