<?php

declare(strict_types=1);

/**
 * Derafu: Merger - Combine Multiple Files Into One, By Format.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsMerger\Exception;

use Derafu\Merger\Exception\MergerException;
use Derafu\Merger\Exception\MissingOptionalDependencyException;
use Derafu\Merger\Exception\UnsupportedMimeTypeException;
use Derafu\Translation\Contract\TranslatableInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The exceptions of the package can be translated, and say the same as before.
 */
#[CoversClass(MergerException::class)]
#[CoversClass(MissingOptionalDependencyException::class)]
#[CoversClass(UnsupportedMimeTypeException::class)]
final class MergerExceptionsTest extends TestCase
{
    public function testTheBaseExceptionIsTranslatable(): void
    {
        $exception = new MergerException('Failed.');

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertSame('Failed.', $exception->getMessage());
    }

    public function testAMissingDependencyIsATranslatableError(): void
    {
        $exception = MissingOptionalDependencyException::forFeature('PdfMerger', 'mpdf/mpdf');

        $this->assertInstanceOf(MergerException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertSame(
            'PdfMerger requires mpdf/mpdf, which is not installed. Run: composer require mpdf/mpdf',
            $exception->getMessage()
        );
    }

    public function testAnUnsupportedMimeTypeIsATranslatableError(): void
    {
        $exception = UnsupportedMimeTypeException::forMimeType('image/png');

        $this->assertInstanceOf(MergerException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertSame(
            'No merger registered supports the MIME type "image/png".',
            $exception->getMessage()
        );
    }
}
