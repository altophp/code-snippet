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
final readonly class CodeLine implements \JsonSerializable
{
    /**
     * @var list<CodeAnnotation>
     */
    private array $annotations;

    /**
     * @param list<CodeAnnotation> $annotations
     */
    public function __construct(
        public int $index,
        public int $number,
        public string $code,
        public bool $selected = false,
        array $annotations = [],
    ) {
        if ($index < 1) {
            throw new \InvalidArgumentException('The snippet line index must be positive.');
        }

        if ($number < 1) {
            throw new \InvalidArgumentException('The source line number must be positive.');
        }

        foreach ($annotations as $annotation) {
            if ($annotation->endOffset() > strlen($code)) {
                throw new \InvalidArgumentException('The annotation range exceeds the code line.');
            }
        }

        usort($annotations, self::compareAnnotations(...));
        $this->annotations = $annotations;
    }

    /**
     * @return list<CodeAnnotation>
     */
    public function annotations(): array
    {
        return $this->annotations;
    }

    /**
     * Split the line wherever its active annotations change.
     *
     * @return list<CodeSegment>
     */
    public function segments(): array
    {
        if ('' === $this->code) {
            return [];
        }

        $boundaries = [0, strlen($this->code)];

        foreach ($this->annotations as $annotation) {
            $boundaries[] = $annotation->offset;
            $boundaries[] = $annotation->endOffset();
        }

        $boundaries = array_values(array_unique($boundaries));
        sort($boundaries);

        $segments = [];

        for ($index = 0, $count = count($boundaries) - 1; $index < $count; ++$index) {
            $start = $boundaries[$index];
            $end = $boundaries[$index + 1];
            $active = array_values(array_filter(
                $this->annotations,
                static fn(CodeAnnotation $annotation): bool => $annotation->offset <= $start
                    && $annotation->endOffset() >= $end,
            ));

            $segments[] = new CodeSegment(
                offset: $start,
                text: substr($this->code, $start, $end - $start),
                annotations: $active,
            );
        }

        return $segments;
    }

    /**
     * @return array{
     *     index: int,
     *     number: int,
     *     code: string,
     *     selected: bool,
     *     annotations: list<array{offset: int, length: int, type: string, data: array<string, mixed>}>
     * }
     */
    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'number' => $this->number,
            'code' => $this->code,
            'selected' => $this->selected,
            'annotations' => array_map(
                static fn(CodeAnnotation $annotation): array => $annotation->toArray(),
                $this->annotations,
            ),
        ];
    }

    /**
     * @return array{
     *     index: int,
     *     number: int,
     *     code: string,
     *     selected: bool,
     *     annotations: list<array{offset: int, length: int, type: string, data: array<string, mixed>}>
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function compareAnnotations(CodeAnnotation $left, CodeAnnotation $right): int
    {
        return [$left->offset, $left->length, $left->type]
            <=> [$right->offset, $right->length, $right->type];
    }
}
