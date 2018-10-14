<?php
namespace Nes\Bus;

class Rom
{
    /** @var int[] */
    public $rom = [];

    public function __construct(array $data)
    {
        $this->rom = $data;
    }

    public function size()
    {
        return count($this->rom);
    }

    public function read(int $addr)
    {
        if (! isset($this->rom[$addr])) {
            throw new \RuntimeException(sprintf(
                "Invalid address on rom read. Address: 0x%s Rom: 0x0000 - 0x%s",
                dechex($addr),
                dechex(count($this->rom))
            ));
        }
        return $this->rom[$addr];
    }
}
