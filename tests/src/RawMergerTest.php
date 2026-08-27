<?php

declare(strict_types=1);

/**
 * Derafu: Merger - Combine Multiple Files Into One, By Format.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsMerger;

use Derafu\Merger\Exception\MergerException;
use Derafu\Merger\Type\RawMerger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RawMerger::class)]
class RawMergerTest extends TestCase
{
    public function testMergesByPlainConcatenation(): void
    {
        $merger = new RawMerger();

        $result = $merger->merge(['abc', 'def', 'ghi']);

        $this->assertSame('abcdefghi', $result);
    }

    public function testMergesWithACustomSeparator(): void
    {
        $merger = new RawMerger();

        $result = $merger->merge(['abc', 'def'], ['separator' => '|']);

        $this->assertSame('abc|def', $result);
    }

    public function testMergingASingleContentReturnsItUnchanged(): void
    {
        $merger = new RawMerger();

        $this->assertSame('abc', $merger->merge(['abc']));
    }

    public function testThrowsWhenMergingAnEmptyList(): void
    {
        $merger = new RawMerger();

        $this->expectException(MergerException::class);

        $merger->merge([]);
    }

    public function testSupportsAnyMimeTypeAsTheCatchAll(): void
    {
        $merger = new RawMerger();

        $this->assertTrue($merger->supports('application/pdf'));
        $this->assertTrue($merger->supports('text/html'));
        $this->assertTrue($merger->supports('application/octet-stream'));
        $this->assertTrue($merger->supports('whatever/unknown'));
    }
}
