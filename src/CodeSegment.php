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
final readonly class CodeSegment implements \JsonSerializable
{
    /**
     * @param list<CodeAnnotation> $annotations
     */
    public function __construct(
        public int $offset,
        public string $text,
        public array $annotations = [],
    ) {
        if ($offset < 0) {
            throw new \InvalidArgumentException('The segment offset cannot be negative.');
        }

        if ('' === $text) {
            throw new \InvalidArgumentException('The segment text cannot be empty.');
        }
    }

    public function length(): int
    {
        return strlen($this->text);
    }

    /**
     * @return array{
     *     offset: int,
     *     length: int,
     *     text: string,
     *     annotations: list<array{offset: int, length: int, type: string, data: array<string, mixed>}>
     * }
     */
    public function toArray(): array
    {
        return [
            'offset' => $this->offset,
            'length' => $this->length(),
            'text' => $this->text,
            'annotations' => array_map(
                static fn(CodeAnnotation $annotation): array => $annotation->toArray(),
                $this->annotations,
            ),
        ];
    }

    /**
     * @return array{
     *     offset: int,
     *     length: int,
     *     text: string,
     *     annotations: list<array{offset: int, length: int, type: string, data: array<string, mixed>}>
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
