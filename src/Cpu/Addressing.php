<?php
namespace Nes\Cpu;

use Nes\Enum;

class Addressing extends Enum
{
    // @codingStandardsIgnoreStart
    const Immediate = 'immediate';
    const ZeroPage = 'zeroPage';
    const Relative = 'relative';
    const Implied = 'implied';
    const Absolute = 'absolute';
    const Accumulator = 'accumulator';
    const ZeroPageX = 'zeroPageX';
    const ZeroPageY = 'zeroPageY';
    const AbsoluteX = 'absoluteX';
    const AbsoluteY = 'absoluteY';
    const PreIndexedIndirect = 'preIndexedIndirect';
    const PostIndexedIndirect = 'postIndexedIndirect';
    const IndirectAbsolute = 'indirectAbsolute';
    // @codingStandardsIgnoreEnd
}
