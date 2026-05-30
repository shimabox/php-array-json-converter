<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\JsonFormatter;
use PHPUnit\Framework\TestCase;

final class JsonFormatterTest extends TestCase
{
    /**
     * PHPの値をpretty printされたJSONに整形する基本仕様を確認します。
     * 日本語をunicode escapeせず、そのまま出力することもカバーします。
     */
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

    /**
     * `1.0` のようなfloatのゼロ小数部をJSON出力で保持することを確認します。
     */
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
