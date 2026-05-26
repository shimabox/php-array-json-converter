<?php

declare(strict_types=1);

namespace PhpArrayJson\Tests;

use PHPUnit\Framework\TestCase;
use PhpArrayJson\JsonFormatter;

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
            $actual
        );
    }
}
