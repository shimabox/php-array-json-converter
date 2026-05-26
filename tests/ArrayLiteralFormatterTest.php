<?php

declare(strict_types=1);

namespace PhpArrayJson\Tests;

use PHPUnit\Framework\TestCase;
use PhpArrayJson\ArrayLiteralFormatter;

final class ArrayLiteralFormatterTest extends TestCase
{
    public function testFormatsAssociativeArrayAsShortArrayLiteral(): void
    {
        $formatter = new ArrayLiteralFormatter();

        $actual = $formatter->format([
            'name' => 'shimabox',
            'age' => 40,
            'active' => true,
            'skills' => ['PHP', 'Go'],
        ]);

        self::assertSame(
            <<<'PHP'
[
    'name' => 'shimabox',
    'age' => 40,
    'active' => true,
    'skills' => [
        'PHP',
        'Go',
    ],
]
PHP,
            $actual
        );
    }

    public function testFormatsEmptyArrayOnOneLine(): void
    {
        $formatter = new ArrayLiteralFormatter();

        self::assertSame('[]', $formatter->format([]));
    }

    public function testEscapesStringKeysAndValues(): void
    {
        $formatter = new ArrayLiteralFormatter();

        $actual = $formatter->format([
            "owner's path" => "C:\\tmp\\owner's-file",
        ]);

        self::assertSame(
            <<<'PHP'
[
    'owner\'s path' => 'C:\\tmp\\owner\'s-file',
]
PHP,
            $actual
        );
    }
}
