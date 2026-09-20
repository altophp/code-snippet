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

namespace Alto\Code\Snippet\Tests;

use Alto\Code\Snippet\CodeAnnotation;
use Alto\Code\Snippet\CodeSnippet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TextAnnotationTest extends TestCase
{
    public function testHighlightPreservesCodeMetadataAndExistingAnnotations(): void
    {
        $source = CodeSnippet::fromCode('sum + sum', 'php', 'test.php', 12)
            ->selectLines(1)
            ->annotate(new CodeAnnotation(0, 3, 'syntax'));
        $snippet = $source->highlight('sum');

        self::assertSame('sum + sum', $snippet->code());
        self::assertSame($source->language(), $snippet->language());
        self::assertSame('test.php', $snippet->sourceName());
        self::assertSame(12, $snippet->startLine());
        self::assertSame([1], $snippet->selectedLines());
        self::assertCount(1, $source->annotations());
        self::assertEquals([
            new CodeAnnotation(0, 3, 'focus'),
            new CodeAnnotation(0, 3, 'syntax'),
            new CodeAnnotation(6, 3, 'focus'),
        ], $snippet->annotations());
        self::assertEquals($snippet->toArray(), $snippet->highlight('sum')->toArray());
    }

    public function testItCanTargetOneOccurrenceAndAttachCustomData(): void
    {
        $source = CodeSnippet::fromCode('sum sum sum');
        self::assertEquals([new CodeAnnotation(4, 3, 'focus')], $source->highlight('sum', occurrence: 2)->annotations());
        self::assertEquals([new CodeAnnotation(8, 3, 'warning', ['label' => 'Check this'])], $source->annotateText(
            'sum',
            'warning',
            ['label' => 'Check this'],
            occurrence: 3,
        )->annotations());
        self::assertEquals([new CodeAnnotation(0, 3, 'focus')], $source->highlight('sum', occurrence: 1)->annotations());
    }

    public function testMissingMatchesLeaveTheSnippetUnchanged(): void
    {
        $source = CodeSnippet::fromCode('sum');
        self::assertSame($source, $source->highlight('SUM'));
        self::assertSame($source, $source->highlight('sum', occurrence: 2));
        $empty = CodeSnippet::fromCode('');
        self::assertSame($empty, $empty->highlight('sum'));
    }

    public function testMatchesAreLiteralNonOverlappingSubstrings(): void
    {
        self::assertEquals([
            new CodeAnnotation(0, 2, 'focus'),
            new CodeAnnotation(2, 2, 'focus'),
        ], CodeSnippet::fromCode('aaaaa')->highlight('aa')->annotations());
        self::assertEquals([new CodeAnnotation(2, 2, 'focus')], CodeSnippet::fromCode('x .* y')->highlight('.*')->annotations());
        self::assertEquals([new CodeAnnotation(0, 3, 'focus')], CodeSnippet::fromCode('summary')->highlight('sum')->annotations());
    }

    public function testUnicodeRangesRemainAlignedThroughIndentationAndSlicing(): void
    {
        $source = CodeSnippet::fromCode("    café();\r\n    café();", startLine: 10)->highlight('café');
        self::assertEquals([
            new CodeAnnotation(4, 5, 'focus'),
            new CodeAnnotation(18, 5, 'focus'),
        ], $source->annotations());

        $snippet = $source->dedent()->indent(2);
        self::assertSame("  café();\r\n  café();", $snippet->code());
        foreach ($snippet->annotations() as $annotation) {
            self::assertSame('café', substr($snippet->code(), $annotation->offset, $annotation->length));
        }
        $slice = $snippet->slice(12, strlen($snippet->code()));
        self::assertSame(11, $slice->startLine());
        self::assertEquals([new CodeAnnotation(2, 5, 'focus')], $slice->annotations());
    }

    public function testItSupportsMultilineText(): void
    {
        $snippet = CodeSnippet::fromCode("first\r\nsecond")->highlight("st\r\nse");
        self::assertEquals([new CodeAnnotation(3, 6, 'focus')], $snippet->annotations());
        self::assertEquals([new CodeAnnotation(3, 2, 'focus')], $snippet->lines()[0]->annotations());
        self::assertEquals([new CodeAnnotation(0, 2, 'focus')], $snippet->lines()[1]->annotations());
    }

    #[DataProvider('invalidArguments')]
    public function testItRejectsInvalidArgumentsEvenWhenNothingMatches(string $text, string $type, ?int $occurrence): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CodeSnippet::fromCode('')->annotateText($text, $type, occurrence: $occurrence);
    }

    /**
     * @return iterable<string, array{string, string, int|null}>
     */
    public static function invalidArguments(): iterable
    {
        yield 'empty text' => ['', 'focus', null];
        yield 'empty type' => ['sum', ' ', null];
        yield 'zero occurrence' => ['sum', 'focus', 0];
        yield 'negative occurrence' => ['sum', 'focus', -1];
    }
}
