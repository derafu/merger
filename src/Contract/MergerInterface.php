<?php

declare(strict_types=1);

/**
 * Derafu: Merger - Combine Multiple Files Into One, By Format.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Merger\Contract;

use Derafu\Merger\Exception\UnsupportedMimeTypeException;

/**
 * Resolves and delegates to the registered `FormatMergerInterface` that
 * supports a given MIME type.
 */
interface MergerInterface
{
    /**
     * Registers a merger. Added after any already registered, so it is only
     * used if none of the earlier ones support the requested MIME type.
     *
     * @param FormatMergerInterface $merger
     * @return static
     */
    public function addMerger(FormatMergerInterface $merger): static;

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
    ): string;

    /**
     * Gets the registered merger that supports the given MIME type.
     *
     * @param string $mimeType
     * @return FormatMergerInterface
     * @throws UnsupportedMimeTypeException If no registered merger supports
     * `$mimeType`.
     */
    public function getMerger(string $mimeType): FormatMergerInterface;
}
