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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CodeAnnotationTest extends TestCase
{
    public function testItCarriesARangeTypeAndGenericData(): void
    {
        $annotation = new CodeAnnotation(4, 3, 'syntax', ['scope' => 'keyword']);

        self::assertSame(7, $annotation->endOffset());
        self::assertSame([
            'offset' => 4,
            'length' => 3,
            'type' => 'syntax',
            'data' => ['scope' => 'keyword'],
        ], $annotation->jsonSerialize());
    }

    /**
     * @return iterable<string, array{int, int, string, string}>
     */
    public static function invalidAnnotationProvider(): iterable
    {
        yield 'negative offset' => [-1, 1, 'syntax', 'offset cannot be negative'];
        yield 'empty range' => [0, 0, 'syntax', 'length must be positive'];
        yield 'empty type' => [0, 1, '  ', 'type cannot be empty'];
    }

    #[DataProvider('invalidAnnotationProvider')]
    public function testItRejectsInvalidAnnotations(
        int $offset,
        int $length,
        string $type,
        string $message,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new CodeAnnotation($offset, $length, $type);
    }
}
