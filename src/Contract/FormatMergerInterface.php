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

use Derafu\Merger\Exception\MergerException;

/**
 * A format merger combines multiple contents of one specific MIME type into
 * a single one (ex. `RawMerger`, `HtmlMerger`, `PdfMerger`).
 *
 * Implementations know nothing about where the contents came from or why
 * there is more than one — they only know how to combine raw content of one
 * specific MIME type. Whoever calls `merge()` is responsible for deciding
 * what to merge, in what order, and how many times each content should be
 * repeated (already expanded into `$contents` before calling).
 */
interface FormatMergerInterface
{
    /**
     * Merges multiple contents into a single one.
     *
     * @param string[] $contents Contents to merge, already expanded and in
     * the desired order. Must contain at least one element.
     * @param array<string,mixed> $options Merge options, specific to each
     * merger implementation.
     * @return string The merged content.
     * @throws MergerException If `$contents` is empty, or the merge fails.
     */
    public function merge(array $contents, array $options = []): string;

    /**
     * Determines whether this merger can handle the given MIME type.
     *
     * @param string $mimeType
     * @return bool
     */
    public function supports(string $mimeType): bool;
}
