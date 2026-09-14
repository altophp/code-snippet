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
use Alto\Code\Snippet\CodeSegment;
use PHPUnit\Framework\TestCase;

final class CodeSegmentTest extends TestCase
{
    public function testItExportsTextAndItsActiveAnnotations(): void
    {
        $annotation = new CodeAnnotation(0, 6, 'syntax', ['scope' => 'keyword']);
        $segment = new CodeSegment(4, 'public', [$annotation]);

        self::assertSame(6, $segment->length());
        self::assertSame([
            'offset' => 4,
            'length' => 6,
            'text' => 'public',
            'annotations' => [$annotation->toArray()],
        ], $segment->jsonSerialize());
    }

    public function testItRejectsANegativeOffset(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('segment offset cannot be negative');

        new CodeSegment(-1, 'code');
    }

    public function testItRejectsEmptyText(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('segment text cannot be empty');

        new CodeSegment(0, '');
    }
}
