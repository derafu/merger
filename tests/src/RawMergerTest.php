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
use Derafu\Merger\Translation\MergerTranslationResourceProvider;
use Derafu\Merger\Type\RawMerger;
use Derafu\Translation\TranslatorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

#[CoversClass(RawMerger::class)]
#[UsesClass(MergerTranslationResourceProvider::class)]
class RawMergerTest extends TestCase
{
    /** @return iterable<string, array{string|null, string}> */
    public static function validSeparators(): iterable
    {
        yield 'null uses the default' => [null, 'abcdef'];
        yield 'empty string' => ['', 'abcdef'];
        yield 'zero string' => ['0', 'abc0def'];
        yield 'spaces are preserved' => ['  ', 'abc  def'];
        yield 'custom separator' => ['|', 'abc|def'];
    }

    #[DataProvider('validSeparators')]
    public function testPreservesValidSeparatorsAndTheNullDefault(?string $separator, string $expected): void
    {
        $this->assertSame($expected, (new RawMerger())->merge(['abc', 'def'], ['separator' => $separator]));
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidSeparators(): iterable
    {
        yield 'integer' => [123];
        yield 'zero' => [0];
        yield 'float' => [1.5];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'empty array' => [[]];
        yield 'array' => [['|']];
        yield 'object' => [new stdClass()];
        yield 'stringable' => [new class () implements Stringable {
            public function __toString(): string
            {
                return '|';
            }
        }];
    }

    #[DataProvider('invalidSeparators')]
    public function testRejectsInvalidSeparatorTypesWithATranslatableException(mixed $separator): void
    {
        $type = get_debug_type($separator);

        try {
            (new RawMerger())->merge(['abc', 'def'], ['separator' => $separator]);
            $this->fail('An invalid separator must be rejected.');
        } catch (MergerException $exception) {
            $this->assertSame("The merge separator must be a string; $type given.", $exception->getMessage());
            $translator = TranslatorFactory::create('es', resourceProviders: [new MergerTranslationResourceProvider()]);
            $this->assertSame("El separador de unión debe ser un string; se recibió $type.", $exception->trans($translator));
        }
    }

    public function testRejectsAResourceSeparator(): void
    {
        $resource = fopen('php://temp', 'w+');
        $this->assertIsResource($resource);
        try {
            $this->testRejectsInvalidSeparatorTypesWithATranslatableException($resource);
        } finally {
            fclose($resource);
        }
    }

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
