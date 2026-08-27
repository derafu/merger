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

use Derafu\Merger\Contract\MergerInterface;
use Derafu\Merger\Exception\UnsupportedMimeTypeException;

/**
 * Main facade: resolves and delegates to the registered `MergerInterface`
 * that supports a given MIME type.
 *
 * Instance-based (not static, unlike `Derafu\Selector\Selector`) so it can
 * be built with `MergerFactory::create()` for the common case, or assembled
 * by hand (or via a DI container) with a custom list of mergers — including
 * one for a MIME type this package does not natively support.
 *
 * Mergers are tried in registration order; the first one whose `supports()`
 * returns `true` is used. Register a catch-all merger (one whose
 * `supports()` always returns `true`, e.g. `RawMerger`) last, so it never
 * shadows a more specific one.
 */
final class Merger
{
    /**
     * @var MergerInterface[]
     */
    private array $mergers = [];

    /**
     * @param iterable<MergerInterface> $mergers
     */
    public function __construct(iterable $mergers = [])
    {
        foreach ($mergers as $merger) {
            $this->addMerger($merger);
        }
    }

    /**
     * Registers a merger. Added after any already registered, so it is only
     * used if none of the earlier ones support the requested MIME type.
     *
     * @param MergerInterface $merger
     * @return static
     */
    public function addMerger(MergerInterface $merger): static
    {
        $this->mergers[] = $merger;

        return $this;
    }

    /**
     * Merges multiple contents of the given MIME type into a single one.
     *
     * @param string[] $contents Contents to merge, already expanded and in
     * the desired order. Must contain at least one element.
     * @param string $mimeType MIME type of every content in `$contents`.
     * @param array<string,mixed> $options Merge options, specific to
     * whichever merger ends up handling `$mimeType`.
     * @return string The merged content.
     * @throws UnsupportedMimeTypeException If no registered merger supports
     * `$mimeType`.
     */
    public function merge(
        array $contents,
        string $mimeType,
        array $options = []
    ): string {
        return $this->getMerger($mimeType)->merge($contents, $options);
    }

    /**
     * Gets the registered merger that supports the given MIME type.
     *
     * @param string $mimeType
     * @return MergerInterface
     * @throws UnsupportedMimeTypeException If no registered merger supports
     * `$mimeType`.
     */
    public function getMerger(string $mimeType): MergerInterface
    {
        foreach ($this->mergers as $merger) {
            if ($merger->supports($mimeType)) {
                return $merger;
            }
        }

        throw UnsupportedMimeTypeException::forMimeType($mimeType);
    }
}
