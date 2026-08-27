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

use Derafu\Merger\Factory\MergerFactory;
use Derafu\Merger\Merger;
use Derafu\Merger\Type\HtmlMerger;
use Derafu\Merger\Type\PdfMerger;
use Derafu\Merger\Type\RawMerger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MergerFactory::class)]
#[CoversClass(Merger::class)]
#[CoversClass(HtmlMerger::class)]
#[CoversClass(PdfMerger::class)]
#[CoversClass(RawMerger::class)]
class MergerFactoryTest extends TestCase
{
    public function testCreatesAMerger(): void
    {
        $this->assertInstanceOf(Merger::class, MergerFactory::create());
    }

    public function testRegistersPdfMergerWhenMpdfIsInstalled(): void
    {
        // `mpdf/mpdf` is a `require-dev` dependency of this package, so it
        // is always installed while running its own test suite.
        $merger = MergerFactory::create();

        $this->assertInstanceOf(PdfMerger::class, $merger->getMerger('application/pdf'));
    }

    public function testRegistersHtmlMerger(): void
    {
        $merger = MergerFactory::create();

        $this->assertInstanceOf(HtmlMerger::class, $merger->getMerger('text/html'));
    }

    public function testRegistersRawMergerAsTheCatchAll(): void
    {
        $merger = MergerFactory::create();

        $this->assertInstanceOf(RawMerger::class, $merger->getMerger('whatever/unknown'));
    }
}
