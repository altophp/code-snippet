<?php

declare(strict_types=1);

/*
 * This file is part of the ALTO library.
 *
 * © 2026-present Simon André
 *
 * For full copyright and license information, please see
 * the LICENSE file distributed with this source code.
 */

namespace Alto\Code\Snippet;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class CodeAnnotation implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public int $offset,
        public int $length,
        public string $type,
        public array $data = [],
    ) {
        if ($offset < 0) {
            throw new \InvalidArgumentException('The annotation offset cannot be negative.');
        }

        if ($length < 1) {
            throw new \InvalidArgumentException('The annotation length must be positive.');
        }

        if ('' === trim($type)) {
            throw new \InvalidArgumentException('The annotation type cannot be empty.');
        }
    }

    public function endOffset(): int
    {
        return $this->offset + $this->length;
    }

    /**
     * @return array{offset: int, length: int, type: string, data: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'offset' => $this->offset,
            'length' => $this->length,
            'type' => $this->type,
            'data' => $this->data,
        ];
    }

    /**
     * @return array{offset: int, length: int, type: string, data: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
