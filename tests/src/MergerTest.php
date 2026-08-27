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

use Derafu\Merger\Contract\FormatMergerInterface;
use Derafu\Merger\Exception\UnsupportedMimeTypeException;
use Derafu\Merger\Merger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Merger::class)]
#[CoversClass(UnsupportedMimeTypeException::class)]
class MergerTest extends TestCase
{
    public function testDelegatesToTheFirstRegisteredMergerThatSupportsTheMimeType(): void
    {
        $merger = new Merger([
            $this->fakeMerger('text/html', 'from-html-merger'),
            $this->fakeMerger('application/pdf', 'from-pdf-merger'),
        ]);

        $this->assertSame(
            'from-pdf-merger',
            $merger->merge(['a', 'b'], 'application/pdf')
        );
    }

    public function testAddMergerRegistersItAfterAnyAlreadyAdded(): void
    {
        $merger = new Merger([
            $this->fakeMerger('application/pdf', 'first'),
        ]);
        $merger->addMerger($this->fakeMerger('application/pdf', 'second'));

        $this->assertSame('first', $merger->merge(['a'], 'application/pdf'));
    }

    public function testACatchAllMergerRegisteredLastDoesNotShadowAnEarlierSpecificOne(): void
    {
        $merger = new Merger([
            $this->fakeMerger('application/pdf', 'specific'),
            $this->fakeMerger(null, 'catch-all', catchAll: true),
        ]);

        $this->assertSame('specific', $merger->merge(['a'], 'application/pdf'));
        $this->assertSame('catch-all', $merger->merge(['a'], 'text/html'));
    }

    public function testGetMergerReturnsTheRegisteredMergerForAMimeType(): void
    {
        $htmlMerger = $this->fakeMerger('text/html', 'html');
        $merger = new Merger([$htmlMerger]);

        $this->assertSame($htmlMerger, $merger->getMerger('text/html'));
    }

    public function testThrowsWhenNoRegisteredMergerSupportsTheMimeType(): void
    {
        $merger = new Merger([$this->fakeMerger('text/html', 'html')]);

        $this->expectException(UnsupportedMimeTypeException::class);

        $merger->merge(['a'], 'application/pdf');
    }

    public function testPassesOptionsThroughToTheResolvedMerger(): void
    {
        $merger = new Merger([
            new class () implements FormatMergerInterface {
                public function merge(array $contents, array $options = []): string
                {
                    return serialize($options);
                }

                public function supports(string $mimeType): bool
                {
                    return true;
                }
            },
        ]);

        $result = $merger->merge(['a'], 'text/html', ['pageBreak' => '<hr/>']);

        $this->assertSame(['pageBreak' => '<hr/>'], unserialize($result));
    }

    private function fakeMerger(
        ?string $supportedMimeType,
        string $result,
        bool $catchAll = false
    ): FormatMergerInterface {
        return new class ($supportedMimeType, $result, $catchAll) implements FormatMergerInterface {
            public function __construct(
                private readonly ?string $supportedMimeType,
                private readonly string $result,
                private readonly bool $catchAll
            ) {
            }

            public function merge(array $contents, array $options = []): string
            {
                return $this->result;
            }

            public function supports(string $mimeType): bool
            {
                return $this->catchAll || $mimeType === $this->supportedMimeType;
            }
        };
    }
}
