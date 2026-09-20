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

use Alto\Language\Language;
use Alto\Language\Languages;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class CodeSnippet implements \JsonSerializable
{
    /**
     * @param list<int>            $selectedLines
     * @param list<CodeAnnotation> $annotations
     */
    private function __construct(
        private string $code,
        private ?Language $language,
        private ?string $sourceName,
        private int $startLine,
        private array $selectedLines,
        private array $annotations,
    ) {
        if ($startLine < 1) {
            throw new \InvalidArgumentException('The first source line must be positive.');
        }
    }

    public static function fromCode(
        string $code,
        Language|string|null $language = null,
        ?string $sourceName = null,
        int $startLine = 1,
    ): self {
        return new self(
            $code,
            self::resolveLanguage($language),
            $sourceName,
            $startLine,
            [],
            [],
        );
    }

    public function code(): string
    {
        return $this->code;
    }

    public function language(): ?Language
    {
        return $this->language;
    }

    public function sourceName(): ?string
    {
        return $this->sourceName;
    }

    public function startLine(): int
    {
        return $this->startLine;
    }

    public function endLine(): int
    {
        return $this->startLine + max(0, $this->lineCount() - 1);
    }

    public function lineCount(): int
    {
        return count($this->lineRanges());
    }

    /**
     * @return list<CodeLine>
     */
    public function lines(): array
    {
        $ranges = $this->lineRanges();

        return array_map(
            fn(array $line, int $offset): CodeLine => new CodeLine(
                index: $offset + 1,
                number: $this->startLine + $offset,
                code: $line['code'],
                selected: in_array($offset + 1, $this->selectedLines, true),
                annotations: $this->annotationsForRange($line['start'], $line['end']),
            ),
            $ranges,
            array_keys($ranges),
        );
    }

    public function selectLines(int ...$lines): self
    {
        $selected = array_values(array_unique([...$this->selectedLines, ...$lines]));
        sort($selected);

        foreach ($selected as $line) {
            if ($line < 1 || $line > $this->lineCount()) {
                throw new \InvalidArgumentException(sprintf(
                    'Selected line %d is outside the snippet line range 1-%d.',
                    $line,
                    $this->lineCount(),
                ));
            }
        }

        return new self(
            $this->code,
            $this->language,
            $this->sourceName,
            $this->startLine,
            $selected,
            $this->annotations,
        );
    }

    /**
     * @return list<int>
     */
    public function selectedLines(): array
    {
        return $this->selectedLines;
    }

    public function isLineSelected(int $line): bool
    {
        return in_array($line, $this->selectedLines, true);
    }

    /**
     * Highlight literal text with focus annotations, optionally at one occurrence.
     */
    public function highlight(string $text, ?int $occurrence = null): self
    {
        return $this->annotateText($text, 'focus', occurrence: $occurrence);
    }

    /**
     * Annotate case-sensitive, non-overlapping literal matches.
     *
     * A null occurrence selects all matches; otherwise occurrences are one-based.
     * Missing text or occurrences leave the snippet unchanged.
     *
     * @param array<string, mixed> $data
     */
    public function annotateText(string $text, string $type, array $data = [], ?int $occurrence = null): self
    {
        if ('' === $text) {
            throw new \InvalidArgumentException('The annotation text cannot be empty.');
        }

        if (null !== $occurrence && $occurrence < 1) {
            throw new \InvalidArgumentException('The text occurrence must be positive.');
        }

        if ('' === trim($type)) {
            throw new \InvalidArgumentException('The annotation type cannot be empty.');
        }

        $length = strlen($text);
        $offset = 0;
        $match = 0;
        $annotations = [];

        while (false !== ($offset = strpos($this->code, $text, $offset))) {
            ++$match;

            if (null === $occurrence || $occurrence === $match) {
                $annotations[] = new CodeAnnotation($offset, $length, $type, $data);

                if (null !== $occurrence) {
                    break;
                }
            }

            $offset += $length;
        }

        return [] === $annotations ? $this : $this->annotate(...$annotations);
    }

    public function annotate(CodeAnnotation ...$annotations): self
    {
        $all = $this->annotations;

        foreach ($annotations as $annotation) {
            if ($annotation->endOffset() > strlen($this->code)) {
                throw new \InvalidArgumentException('The annotation range exceeds the snippet code.');
            }

            if (!in_array($annotation, $all, false)) {
                $all[] = $annotation;
            }
        }

        usort($all, self::compareAnnotations(...));

        return new self(
            $this->code,
            $this->language,
            $this->sourceName,
            $this->startLine,
            $this->selectedLines,
            $all,
        );
    }

    /**
     * @return list<CodeAnnotation>
     */
    public function annotations(): array
    {
        return $this->annotations;
    }

    /**
     * Project a byte range from this already annotated snippet.
     *
     * Both offsets are relative to `code()` and the end offset is exclusive.
     * Annotations crossing either boundary are clipped and shifted.
     */
    public function slice(int $startOffset, int $endOffset): self
    {
        $codeLength = strlen($this->code);

        if ($startOffset < 0 || $endOffset < $startOffset || $endOffset > $codeLength) {
            throw new \InvalidArgumentException(sprintf(
                'The snippet range [%d, %d) is outside code offsets 0-%d.',
                $startOffset,
                $endOffset,
                $codeLength,
            ));
        }

        $startLine = $this->startLine + $this->lineOffsetAt($startOffset);
        $annotations = [];

        foreach ($this->annotations as $annotation) {
            $start = max($annotation->offset, $startOffset);
            $end = min($annotation->endOffset(), $endOffset);

            if ($end > $start) {
                $annotations[] = new CodeAnnotation(
                    offset: $start - $startOffset,
                    length: $end - $start,
                    type: $annotation->type,
                    data: $annotation->data,
                );
            }
        }

        $code = substr($this->code, $startOffset, $endOffset - $startOffset);
        $selectedSourceLines = array_map(
            fn(int $line): int => $this->startLine + $line - 1,
            $this->selectedLines,
        );
        $lineCount = self::countLines($code);
        $selectedLines = [];

        for ($line = 1; $line <= $lineCount; ++$line) {
            if (in_array($startLine + $line - 1, $selectedSourceLines, true)) {
                $selectedLines[] = $line;
            }
        }

        return new self(
            $code,
            $this->language,
            $this->sourceName,
            $startLine,
            $selectedLines,
            $annotations,
        );
    }

    public function dedent(): self
    {
        $indentation = null;

        foreach ($this->lines() as $line) {
            if ('' === trim($line->code)) {
                continue;
            }

            $lineIndentation = strspn($line->code, " \t");
            $indentation = null === $indentation
                ? $lineIndentation
                : min($indentation, $lineIndentation);
        }

        if (null === $indentation || 0 === $indentation) {
            return $this;
        }

        return $this->transformLines(
            static fn(CodeLine $line): array => '' === trim($line->code)
                ? ['code' => '', 'removed' => strlen($line->code), 'inserted' => 0]
                : ['code' => substr($line->code, $indentation), 'removed' => $indentation, 'inserted' => 0],
        );
    }

    public function unindent(): self
    {
        return $this->dedent();
    }

    public function indent(int $spaces = 4): self
    {
        if ($spaces < 0) {
            throw new \InvalidArgumentException('The indentation cannot be negative.');
        }

        if (0 === $spaces) {
            return $this;
        }

        $indentation = str_repeat(' ', $spaces);

        return $this->transformLines(
            static fn(CodeLine $line): array => '' === trim($line->code)
                ? ['code' => '', 'removed' => strlen($line->code), 'inserted' => 0]
                : ['code' => $indentation . $line->code, 'removed' => 0, 'inserted' => $spaces],
        );
    }

    /**
     * @return array{
     *     code: string,
     *     language: string|null,
     *     sourceName: string|null,
     *     startLine: int,
     *     endLine: int,
     *     lines: list<array{
     *         index: int,
     *         number: int,
     *         code: string,
     *         selected: bool,
     *         annotations: list<array{offset: int, length: int, type: string, data: array<string, mixed>}>
     *     }>,
     *     selectedLines: list<int>,
     *     annotations: list<array{offset: int, length: int, type: string, data: array<string, mixed>}>
     * }
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'language' => $this->language?->slug,
            'sourceName' => $this->sourceName,
            'startLine' => $this->startLine,
            'endLine' => $this->endLine(),
            'lines' => array_map(
                static fn(CodeLine $line): array => $line->toArray(),
                $this->lines(),
            ),
            'selectedLines' => $this->selectedLines,
            'annotations' => array_map(
                static fn(CodeAnnotation $annotation): array => $annotation->toArray(),
                $this->annotations,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return list<array{code: string, start: int, end: int, break: string}>
     */
    private function lineRanges(): array
    {
        return self::rangesFor($this->code);
    }

    /**
     * @return list<array{code: string, start: int, end: int, break: string}>
     */
    private static function rangesFor(string $code): array
    {
        if ('' === $code) {
            return [];
        }

        $lines = [];
        $start = 0;
        $length = strlen($code);

        for ($offset = 0; $offset < $length; ++$offset) {
            if ("\r" !== $code[$offset] && "\n" !== $code[$offset]) {
                continue;
            }

            $breakLength = "\r" === $code[$offset]
                && $offset + 1 < $length
                && "\n" === $code[$offset + 1]
                    ? 2
                    : 1;

            $lines[] = [
                'code' => substr($code, $start, $offset - $start),
                'start' => $start,
                'end' => $offset,
                'break' => substr($code, $offset, $breakLength),
            ];

            $offset += $breakLength - 1;
            $start = $offset + 1;
        }

        if ($start < $length) {
            $lines[] = [
                'code' => substr($code, $start),
                'start' => $start,
                'end' => $length,
                'break' => '',
            ];
        }

        return $lines;
    }

    /**
     * @return list<CodeAnnotation>
     */
    private function annotationsForRange(int $rangeStart, int $rangeEnd): array
    {
        $annotations = [];

        foreach ($this->annotations as $annotation) {
            $start = max($annotation->offset, $rangeStart);
            $end = min($annotation->endOffset(), $rangeEnd);

            if ($end > $start) {
                $annotations[] = new CodeAnnotation(
                    offset: $start - $rangeStart,
                    length: $end - $start,
                    type: $annotation->type,
                    data: $annotation->data,
                );
            }
        }

        return $annotations;
    }

    /**
     * @param callable(CodeLine): array{code: string, removed: int, inserted: int} $transform
     */
    private function transformLines(callable $transform): self
    {
        $ranges = $this->lineRanges();
        $transformedLines = [];
        $annotations = [];
        $codeOffset = 0;

        foreach ($this->lines() as $index => $line) {
            $result = $transform($line);
            $break = $ranges[$index]['break'];
            $transformedLines[] = $result['code'] . $break;

            foreach ($line->annotations() as $annotation) {
                $start = max($annotation->offset, $result['removed']);
                $end = min($annotation->endOffset(), strlen($line->code));

                if ($end > $start) {
                    $annotations[] = new CodeAnnotation(
                        offset: $codeOffset + $start - $result['removed'] + $result['inserted'],
                        length: $end - $start,
                        type: $annotation->type,
                        data: $annotation->data,
                    );
                }
            }

            $codeOffset += strlen($result['code']) + strlen($break);
        }

        $code = implode('', $transformedLines);

        return new self(
            $code,
            $this->language,
            $this->sourceName,
            $this->startLine,
            $this->selectedLines,
            $annotations,
        );
    }

    private static function countLines(string $code): int
    {
        return count(self::rangesFor($code));
    }

    private function lineOffsetAt(int $offset): int
    {
        $lineOffset = 0;

        foreach ($this->lineRanges() as $line) {
            if ('' === $line['break'] || $line['end'] + strlen($line['break']) > $offset) {
                break;
            }

            ++$lineOffset;
        }

        return $lineOffset;
    }

    private static function compareAnnotations(CodeAnnotation $left, CodeAnnotation $right): int
    {
        return [$left->offset, $left->length, $left->type]
            <=> [$right->offset, $right->length, $right->type];
    }

    private static function resolveLanguage(Language|string|null $language): ?Language
    {
        if (!is_string($language)) {
            return $language;
        }

        return Languages::get($language)
            ?? throw new \InvalidArgumentException(sprintf('Unknown code language "%s".', $language));
    }
}
