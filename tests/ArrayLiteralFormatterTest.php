<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\ArrayLiteralFormatter;
use PhpArrayJsonConverter\ConversionError;
use PHPUnit\Framework\TestCase;

final class ArrayLiteralFormatterTest extends TestCase
{
    /**
     * 連想配列を短縮配列構文のPHP配列リテラルへ整形する基本仕様を確認します。
     * ネストしたリスト配列のインデントとtrailing commaもカバーします。
     */
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
            $actual,
        );
    }

    /**
     * 空配列を1行の `[]` として出力することを確認します。
     */
    public function testFormatsEmptyArrayOnOneLine(): void
    {
        $formatter = new ArrayLiteralFormatter();

        self::assertSame('[]', $formatter->format([]));
    }

    /**
     * 文字列キーと文字列値のsingle quote/backslashをPHPリテラルとして安全にescapeすることを確認します。
     */
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
            $actual,
        );
    }

    /**
     * `1.0` のようなfloatを `1` に崩さずPHP配列リテラルへ整形することを確認します。
     */
    public function testFormatsFloatZeroFraction(): void
    {
        $formatter = new ArrayLiteralFormatter();

        self::assertSame(
            <<<'PHP'
[
    1.0,
]
PHP,
            $formatter->format([1.0]),
        );
    }

    /**
     * 深すぎる配列を整形せずConversionErrorにすることを確認します。
     * 循環参照や過度なネストによる無限再帰・メモリ枯渇を避けるための防御テストです。
     */
    public function testThrowsConversionErrorWhenFormatterDepthLimitIsExceeded(): void
    {
        $formatter = new ArrayLiteralFormatter();
        $value = [];

        for ($index = 0; $index < 129; $index++) {
            $value = [$value];
        }

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('maximum array depth exceeded');

        $formatter->format($value);
    }
}
