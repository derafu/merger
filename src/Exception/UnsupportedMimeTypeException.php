<?php

declare(strict_types=1);

/**
 * Derafu: Merger - Combine Multiple Files Into One, By Format.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Merger\Exception;

/**
 * Thrown when no registered merger supports the requested MIME type.
 *
 * The package service configuration registers a catch-all merger. This
 * exception applies to collections configured without a matching merger
 * or a catch-all implementation.
 */
final class UnsupportedMimeTypeException extends MergerException
{
    public static function forMimeType(string $mimeType): self
    {
        return new self([
            'No merger registered supports the MIME type "{mimeType}".',
            'mimeType' => $mimeType,
        ]);
    }
}
