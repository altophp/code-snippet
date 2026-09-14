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
use Alto\Code\Snippet\CodeLine;
use Alto\Code\Snippet\CodeSnippet;
use Alto\Language\Languages;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CodeSnippetTest extends TestCase
{
    public function testItCarriesCodeLanguageAndSourceMetadata(): void
    {
        $language = Languages::get('php');
        self::assertNotNull($language);

        $snippet = CodeSnippet::fromCode(
            "public function run(): void\n{\n}",
            language: $language,
            sourceName: 'src/Runner.php',
            startLine: 12,
        );

        self::assertSame("public function run(): void\n{\n}", $snippet->code());
        self::assertSame($language, $snippet->language());
        self::assertSame('src/Runner.php', $snippet->sourceName());
        self::assertSame(12, $snippet->startLine());
        self::assertSame(14, $snippet->endLine());
        self::assertSame(3, $snippet->lineCount());
    }

    public function testItResolvesAStringLanguageSlug(): void
    {
        $snippet = CodeSnippet::fromCode('echo $name;', 'php');

        self::assertSame('php', $snippet->language()?->slug);
    }

    public function testItRejectsAnUnknownStringLanguageSlug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown code language "unknown".');

        CodeSnippet::fromCode('anything', 'unknown');
    }

    public function testItRejectsANonPositiveFirstSourceLine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('first source line must be positive');

        CodeSnippet::fromCode('content', startLine: 0);
    }

    public function testItExposesLocalIndexesAndOriginalSourceLineNumbers(): void
    {
        $lines = CodeSnippet::fromCode("first();\nsecond();", startLine: 40)->lines();

        self::assertCount(2, $lines);
        self::assertSame(1, $lines[0]->index);
        self::assertSame(40, $lines[0]->number);
        self::assertSame('first();', $lines[0]->code);
        self::assertSame(2, $lines[1]->index);
        self::assertSame(41, $lines[1]->number);
        self::assertSame('second();', $lines[1]->code);
    }

    public function testItPreservesLineEndingsAndDoesNotInventALineAfterATrailingBreak(): void
    {
        $snippet = CodeSnippet::fromCode("first\r\nsecond\rthird\n", startLine: 20)
            ->selectLines(2)
            ->annotate(new CodeAnnotation(7, 6, 'syntax'));

        self::assertSame("first\r\nsecond\rthird\n", $snippet->code());
        self::assertSame(3, $snippet->lineCount());
        self::assertSame(['first', 'second', 'third'], array_map(
            static fn(CodeLine $line): string => $line->code,
            $snippet->lines(),
        ));
        self::assertSame(0, $snippet->lines()[1]->annotations()[0]->offset);
        self::assertSame('second', $snippet->slice(7, 13)->code());
        self::assertSame(21, $snippet->slice(7, 13)->startLine());
        self::assertSame([1], $snippet->slice(7, 13)->selectedLines());
        self::assertSame("  first\r\n  second\r  third\n", $snippet->indent(2)->code());
    }

    public function testAnEmptySnippetHasNoCodeLines(): void
    {
        $snippet = CodeSnippet::fromCode('', startLine: 8);

        self::assertSame(0, $snippet->lineCount());
        self::assertSame([], $snippet->lines());
        self::assertSame(8, $snippet->endLine());
    }

    public function testItSelectsSnippetRelativeLinesWithoutMutatingTheOriginal(): void
    {
        $snippet = CodeSnippet::fromCode("first\nsecond\nthird", startLine: 20);
        $selected = $snippet->selectLines(3, 1, 3);

        self::assertSame([], $snippet->selectedLines());
        self::assertSame([1, 3], $selected->selectedLines());
        self::assertTrue($selected->isLineSelected(1));
        self::assertFalse($selected->isLineSelected(2));
        self::assertTrue($selected->lines()[2]->selected);
        self::assertSame(22, $selected->lines()[2]->number);
    }

    public function testItRejectsASelectedLineOutsideTheSnippet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the snippet line range 1-2');

        CodeSnippet::fromCode("first\nsecond")->selectLines(3);
    }

    public function testItCarriesOrderedGenericAnnotationsWithoutMutatingTheOriginal(): void
    {
        $snippet = CodeSnippet::fromCode('public function run()');
        $syntax = new CodeAnnotation(0, 6, 'syntax', ['scope' => 'keyword']);
        $emphasis = new CodeAnnotation(16, 3, 'emphasis', ['name' => 'primary']);
        $annotated = $snippet->annotate($emphasis, $syntax, $syntax);

        self::assertSame([], $snippet->annotations());
        self::assertSame([$syntax, $emphasis], $annotated->annotations());
        self::assertEquals([$syntax, $emphasis], $annotated->lines()[0]->annotations());
    }

    public function testItRejectsAnAnnotationOutsideTheCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('annotation range exceeds the snippet code');

        CodeSnippet::fromCode('short')->annotate(new CodeAnnotation(3, 5, 'syntax'));
    }

    public function testMultilineAnnotationsAreProjectedOntoTheirCodeLines(): void
    {
        $snippet = CodeSnippet::fromCode("/**\n * docs\n */")
            ->annotate(new CodeAnnotation(0, 15, 'syntax', ['scope' => 'comment']));

        $lines = $snippet->lines();

        self::assertSame([3, 7, 3], array_map(
            static fn(CodeLine $line): int => $line->annotations()[0]->length,
            $lines,
        ));
        self::assertSame([0, 0, 0], array_map(
            static fn(CodeLine $line): int => $line->annotations()[0]->offset,
            $lines,
        ));
    }

    public function testItSlicesAlreadyAnnotatedCodeAndClipsCrossingAnnotations(): void
    {
        $snippet = CodeSnippet::fromCode("before\n    docblock line\nafter", startLine: 10)
            ->selectLines(2, 3)
            ->annotate(new CodeAnnotation(7, 17, 'syntax', ['scope' => 'comment']));

        $slice = $snippet->slice(11, 20);

        self::assertSame('docblock ', $slice->code());
        self::assertSame(11, $slice->startLine());
        self::assertSame([1], $slice->selectedLines());
        self::assertEquals([
            new CodeAnnotation(0, 9, 'syntax', ['scope' => 'comment']),
        ], $slice->annotations());
    }

    public function testItCanProjectAnEmptyRange(): void
    {
        $slice = CodeSnippet::fromCode('code', startLine: 7)
            ->selectLines(1)
            ->slice(0, 0);

        self::assertSame('', $slice->code());
        self::assertSame(7, $slice->startLine());
        self::assertSame([], $slice->selectedLines());
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidSliceProvider(): iterable
    {
        yield 'negative start' => [-1, 2];
        yield 'reversed range' => [3, 2];
        yield 'end after code' => [0, 6];
    }

    #[DataProvider('invalidSliceProvider')]
    public function testItRejectsInvalidSliceRanges(int $start, int $end): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('outside code offsets');

        CodeSnippet::fromCode('short')->slice($start, $end);
    }

    public function testDedentPreservesAnnotationsSelectionsAndTrailingLineBreak(): void
    {
        $snippet = CodeSnippet::fromCode("    first();\n        second();\n    \n", startLine: 20)
            ->selectLines(2)
            ->annotate(
                new CodeAnnotation(4, 5, 'syntax', ['scope' => 'name']),
                new CodeAnnotation(21, 6, 'emphasis'),
            );

        $dedented = $snippet->dedent();

        self::assertSame("first();\n    second();\n\n", $dedented->code());
        self::assertSame([2], $dedented->selectedLines());
        self::assertEquals([
            new CodeAnnotation(0, 5, 'syntax', ['scope' => 'name']),
            new CodeAnnotation(13, 6, 'emphasis'),
        ], $dedented->annotations());
    }

    public function testDedentReturnsTheSameSnippetWhenThereIsNoCommonIndentation(): void
    {
        $snippet = CodeSnippet::fromCode("content\n    nested");
        $blank = CodeSnippet::fromCode("   \n");

        self::assertSame($snippet, $snippet->dedent());
        self::assertSame($blank, $blank->dedent());
    }

    public function testIndentPreservesAnnotationsAndLeavesBlankLinesEmpty(): void
    {
        $snippet = CodeSnippet::fromCode("first\n   \nsecond")
            ->annotate(new CodeAnnotation(0, 5, 'syntax'));

        $indented = $snippet->indent(2)->indent();

        self::assertSame("      first\n\n      second", $indented->code());
        self::assertEquals(new CodeAnnotation(6, 5, 'syntax'), $indented->annotations()[0]);
        self::assertSame($snippet, $snippet->indent(0));
    }

    public function testIndentRejectsANegativeNumberOfSpaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('indentation cannot be negative');

        CodeSnippet::fromCode('first')->indent(-1);
    }

    public function testUnindentIsAnAliasOfDedent(): void
    {
        $snippet = CodeSnippet::fromCode("    first\n        second");

        self::assertEquals($snippet->dedent(), $snippet->unindent());
    }

    public function testItExportsPresentationNeutralAnnotatedLines(): void
    {
        $language = Languages::get('php');
        self::assertNotNull($language);

        $annotation = new CodeAnnotation(9, 6, 'emphasis');
        $snippet = CodeSnippet::fromCode(
            "first();\nsecond();",
            language: $language,
            sourceName: 'Example.php',
            startLine: 9,
        )
            ->selectLines(2)
            ->annotate($annotation);

        self::assertSame([
            'code' => "first();\nsecond();",
            'language' => 'php',
            'sourceName' => 'Example.php',
            'startLine' => 9,
            'endLine' => 10,
            'lines' => [
                [
                    'index' => 1,
                    'number' => 9,
                    'code' => 'first();',
                    'selected' => false,
                    'annotations' => [],
                ],
                [
                    'index' => 2,
                    'number' => 10,
                    'code' => 'second();',
                    'selected' => true,
                    'annotations' => [new CodeAnnotation(0, 6, 'emphasis')->toArray()],
                ],
            ],
            'selectedLines' => [2],
            'annotations' => [$annotation->toArray()],
        ], $snippet->jsonSerialize());
    }
}
