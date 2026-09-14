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
use Alto\Code\Snippet\CodeSegment;
use PHPUnit\Framework\TestCase;

final class CodeLineTest extends TestCase
{
    public function testItSplitsOverlappingAnnotationsIntoRenderableSegments(): void
    {
        $syntax = new CodeAnnotation(0, 4, 'syntax', ['scope' => 'keyword']);
        $emphasis = new CodeAnnotation(2, 3, 'emphasis');
        $line = new CodeLine(2, 42, 'abcdef', true, [$emphasis, $syntax]);

        self::assertSame([$syntax, $emphasis], $line->annotations());
        self::assertSame([
            ['text' => 'ab', 'types' => ['syntax']],
            ['text' => 'cd', 'types' => ['syntax', 'emphasis']],
            ['text' => 'e', 'types' => ['emphasis']],
            ['text' => 'f', 'types' => []],
        ], array_map(
            static fn(CodeSegment $segment): array => [
                'text' => $segment->text,
                'types' => array_map(
                    static fn(CodeAnnotation $annotation): string => $annotation->type,
                    $segment->annotations,
                ),
            ],
            $line->segments(),
        ));
        self::assertSame([
            'index' => 2,
            'number' => 42,
            'code' => 'abcdef',
            'selected' => true,
            'annotations' => [$syntax->toArray(), $emphasis->toArray()],
        ], $line->jsonSerialize());
    }

    public function testAnEmptyLineHasNoSegments(): void
    {
        self::assertSame([], (new CodeLine(1, 1, ''))->segments());
    }

    public function testItRejectsANonPositiveIndex(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CodeLine(0, 1, '');
    }

    public function testItRejectsANonPositiveSourceNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CodeLine(1, 0, '');
    }

    public function testItRejectsAnAnnotationOutsideTheLine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('annotation range exceeds the code line');

        new CodeLine(1, 1, 'short', annotations: [new CodeAnnotation(3, 3, 'syntax')]);
    }
}
