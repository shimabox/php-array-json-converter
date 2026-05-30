<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\JsonFormatter;
use PHPUnit\Framework\TestCase;

final class JsonFormatterTest extends TestCase
{
    public function testFormatsValueAsPrettyJsonWithoutEscapingUnicode(): void
    {
        $formatter = new JsonFormatter();

        $actual = $formatter->format([
            'name' => 'shimabox',
            'location' => '東京',
            'skills' => ['PHP', 'Go'],
        ]);

        self::assertSame(
            <<<'JSON'
{
    "name": "shimabox",
    "location": "東京",
    "skills": [
        "PHP",
        "Go"
    ]
}
JSON,
            $actual,
        );
    }

    public function testPreservesZeroFraction(): void
    {
        $formatter = new JsonFormatter();

        self::assertSame(
            <<<'JSON'
[
    1.0
]
JSON,
            $formatter->format([1.0]),
        );
    }
}
