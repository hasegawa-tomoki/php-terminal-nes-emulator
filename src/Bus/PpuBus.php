<?php
namespace Nes\Bus;

use Nes\Mapper\MapperInterface;

class PpuBus
{
    /** @var \Nes\Mapper\MapperInterface */
    public $mapper;

    public function __construct(MapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    public function readByPpu(int $addr): int
    {
        return $this->mapper->readChr($addr);
    }

    public function writeByPpu(int $addr, int $data)
    {
        $this->mapper->writeChr($addr, $data);
    }
}
