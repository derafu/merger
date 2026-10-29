<?php

declare(strict_types=1);

/**
 * Derafu: Merger - Combine Multiple Files Into One, By Format.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Merger;

use Derafu\Merger\Contract\FormatMergerInterface;
use Derafu\Merger\Contract\MergerInterface;
use Derafu\Merger\Exception\UnsupportedMimeTypeException;

/**
 * Main facade: resolves and delegates to the registered `FormatMergerInterface`
 * that supports a given MIME type.
 *
 * Receives format mergers from the package's service configuration or an
 * explicitly supplied collection, including custom MIME type implementations.
 *
 * Mergers are tried in registration order; the first one whose `supports()`
 * returns `true` is used. Register a catch-all merger (one whose
 * `supports()` always returns `true`, e.g. `RawMerger`) last, so it never
 * shadows a more specific one.
 */
final class Merger implements MergerInterface
{
    /**
     * @var FormatMergerInterface[]
     */
    private array $mergers = [];

    /**
     * @param iterable<FormatMergerInterface> $mergers
     */
    public function __construct(iterable $mergers = [])
    {
        foreach ($mergers as $merger) {
            $this->addMerger($merger);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addMerger(FormatMergerInterface $merger): static
    {
        $this->mergers[] = $merger;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function merge(
        array $contents,
        string $mimeType,
        array $options = []
    ): string {
        return $this->getMerger($mimeType)->merge($contents, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getMerger(string $mimeType): FormatMergerInterface
    {
        foreach ($this->mergers as $merger) {
            if ($merger->supports($mimeType)) {
                return $merger;
            }
        }

        throw UnsupportedMimeTypeException::forMimeType($mimeType);
    }
}
