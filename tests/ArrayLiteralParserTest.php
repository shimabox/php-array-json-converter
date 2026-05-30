<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\ArrayLiteralParser;
use PhpArrayJsonConverter\ConversionError;
use PHPUnit\Framework\TestCase;

final class ArrayLiteralParserTest extends TestCase
{
    /**
     * 基本的な連想配列リテラルをPHPの値へparseできることを確認します。
     * string/int/bool/list配列を含む代表的な正常系です。
     */
    public function testParsesAssociativeArrayLiteral(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    'name' => 'shimabox',
    'age' => 40,
    'active' => true,
    'skills' => ['PHP', 'Go'],
]
PHP);

        self::assertSame([
            'name' => 'shimabox',
            'age' => 40,
            'active' => true,
            'skills' => ['PHP', 'Go'],
        ], $actual);
    }

    /**
     * `<?php` 開始タグと末尾セミコロン付きの入力を許可することを確認します。
     * PHPファイルからコピーした配列リテラルをそのまま扱えるようにするためのケースです。
     */
    public function testParsesArrayLiteralWithOpenTagAndSemicolon(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
<?php

[
    'enabled' => false,
    'limit' => null,
];
PHP);

        self::assertSame([
            'enabled' => false,
            'limit' => null,
        ], $actual);
    }

    /**
     * float、明示的なinteger key、ネストした連想配列をparseできることを確認します。
     */
    public function testParsesFloatIntegerKeyAndNestedAssociativeArray(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    10 => 'ten',
    'ratio' => 0.75,
    'profile' => [
        'location' => 'Tokyo',
    ],
]
PHP);

        self::assertSame([
            10 => 'ten',
            'ratio' => 0.75,
            'profile' => [
                'location' => 'Tokyo',
            ],
        ], $actual);
    }

    /**
     * 空の短縮配列リテラル `[]` を空配列としてparseすることを確認します。
     */
    public function testParsesEmptyArrayLiteral(): void
    {
        $parser = new ArrayLiteralParser();

        self::assertSame([], $parser->parse('[]'));
    }

    /**
     * single-quoted stringのescape仕様を確認します。
     * quoteとbackslashをPHP文字列として解釈できることをカバーします。
     */
    public function testParsesEscapedSingleQuotedString(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    'quote' => 'It\'s ok',
    'slash' => 'C:\\temp',
]
PHP);

        self::assertSame([
            'quote' => "It's ok",
            'slash' => 'C:\temp',
        ], $actual);
    }

    /**
     * 変数展開を含まないdouble-quoted stringを許可することを確認します。
     * 対応済みescape sequenceと、`\$` が文字として扱われることをカバーします。
     */
    public function testParsesDoubleQuotedStringWithoutInterpolation(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    "name" => "shimabox",
    "quote" => "say \"hello\"",
    "line" => "first\nsecond",
    "slash" => "C:\\temp",
    "dollar" => "\$name",
]
PHP);

        self::assertSame([
            'name' => 'shimabox',
            'quote' => 'say "hello"',
            'line' => "first\nsecond",
            'slash' => 'C:\temp',
            'dollar' => '$name',
        ], $actual);
    }

    /**
     * 明示的なinteger keyの後に続く暗黙keyがPHPと同じ採番になることを確認します。
     * PHP配列のkey採番ルールを再現するための重要な互換性テストです。
     */
    public function testParsesImplicitIndexAfterExplicitIntegerKeyLikePhp(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    2 => 'two',
    'three',
    0 => 'zero',
    'four',
]
PHP);

        self::assertSame([
            2 => 'two',
            3 => 'three',
            0 => 'zero',
            4 => 'four',
        ], $actual);
    }

    /**
     * 負のint/float値と負のinteger keyをparseできることを確認します。
     */
    public function testParsesNegativeNumbers(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    'int' => -123,
    'float' => -12.34,
    -1 => 'negative key',
]
PHP);

        self::assertSame([
            'int' => -123,
            'float' => -12.34,
            -1 => 'negative key',
        ], $actual);
    }

    /**
     * `PHP_INT_MAX` のkey後に暗黙keyを採番しようとした場合にConversionErrorにすることを確認します。
     * overflowによるTypeErrorやfloat化を防ぐための回帰テストです。
     */
    public function testThrowsConversionErrorOnIntegerKeyOverflow(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('integer key is too large');

        $parser->parse('[' . PHP_INT_MAX . " => 'max', 'next']");
    }

    /**
     * 16進数など、対応していないinteger literalを拒否することを確認します。
     * `(int) "0x10" === 0` のような誤変換を防ぐためのテストです。
     */
    public function testRejectsUnsupportedIntegerLiteralFormats(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('unsupported integer literal');

        $parser->parse('[0x10]');
    }

    /**
     * underscore区切りのinteger literalを拒否することを確認します。
     * 現時点では10進数字のみを対応範囲として固定します。
     */
    public function testRejectsIntegerLiteralWithUnderscore(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('unsupported integer literal');

        $parser->parse('[1_000]');
    }

    /**
     * 配列の最大深さを超えた入力をConversionErrorにすることを確認します。
     * 過度なネストによる処理時間・メモリ使用量の増加を防ぐための境界テストです。
     */
    public function testThrowsConversionErrorWhenArrayDepthLimitIsExceeded(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('maximum array depth exceeded');

        $parser->parse(str_repeat('[', 129) . str_repeat(']', 129));
    }

    /**
     * 実用上十分な深さのネスト配列は正常にparseできることを確認します。
     */
    public function testParsesDeeplyNestedArrayLiteral(): void
    {
        $parser = new ArrayLiteralParser();

        $actual = $parser->parse(<<<'PHP'
[
    'level1' => [
        'level2' => [
            'level3' => [
                'level4' => [
                    'value' => 'deep',
                ],
            ],
        ],
    ],
]
PHP);

        self::assertSame([
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'value' => 'deep',
                        ],
                    ],
                ],
            ],
        ], $actual);
    }

    /**
     * 関数呼び出しを拒否することを確認します。
     * ユーザー入力をPHPとして実行しないための禁止構文テストです。
     */
    public function testRejectsFunctionCall(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("['name' => getenv('USER')]");
    }

    /**
     * 関数呼び出しを拒否するだけでなく、実際に副作用が起きないことを確認します。
     * `file_put_contents()` を含む入力でもファイルが作られないことを検証する安全性テストです。
     */
    public function testRejectsFunctionCallWithoutExecutingInput(): void
    {
        $parser = new ArrayLiteralParser();
        $path = tempnam(sys_get_temp_dir(), 'array-parser-');
        self::assertIsString($path);
        unlink($path);

        try {
            $parser->parse("['written' => file_put_contents('{$path}', 'executed')]");
            self::fail('Expected ConversionError was not thrown.');
        } catch (ConversionError $error) {
            self::assertStringContainsString('Unsupported PHP syntax', $error->getMessage());
            self::assertFileDoesNotExist($path);
        }
    }

    /**
     * 変数参照を拒否することを確認します。
     * 入力は値リテラルだけを許可し、実行時環境に依存する構文は扱いません。
     */
    public function testRejectsVariable(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("['name' => \$name]");
    }

    /**
     * 定数参照を拒否することを確認します。
     * 未定義/定義済み定数を解決するためにPHPコードを実行しない方針を固定します。
     */
    public function testRejectsConstant(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("['name' => SOME_CONSTANT]");
    }

    /**
     * `array()` 構文を拒否することを確認します。
     * このparserは短縮配列リテラル `[]` だけを対応範囲とします。
     */
    public function testRejectsArrayFunctionSyntax(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("array('name' => 'shimabox')");
    }

    /**
     * root配列の後に余分なtokenが続く入力を拒否することを確認します。
     * 1つの配列リテラル全体だけを入力として受け付ける仕様を固定します。
     */
    public function testRejectsExtraTokenAfterRootArray(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('unexpected token');

        $parser->parse("['name' => 'shimabox']; ['extra']");
    }

    /**
     * double-quoted string内の単純な変数展開を拒否することを確認します。
     * 文字列リテラルは静的な文字だけを許可します。
     */
    public function testRejectsDoubleQuotedStringWithVariableInterpolation(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse('["name" => "$name"]');
    }

    /**
     * double-quoted string内のbraced variable interpolationを拒否することを確認します。
     */
    public function testRejectsDoubleQuotedStringWithBracedVariableInterpolation(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse('["name" => "{$name}"]');
    }

    /**
     * クラス定数参照を拒否することを確認します。
     * 実行時のclass loadingや定数解決を行わないための禁止構文テストです。
     */
    public function testRejectsClassConstant(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("['status' => SomeClass::VALUE]");
    }

    /**
     * 文字列連結式を拒否することを確認します。
     * parserは式評価をせず、リテラル値だけを読み取ります。
     */
    public function testRejectsStringConcatenation(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("['name' => 'shima' . 'box']");
    }

    /**
     * spread operatorを拒否することを確認します。
     * 配列展開は式評価に近いため、現時点の安全な対応範囲から外します。
     */
    public function testRejectsSpreadOperator(): void
    {
        $parser = new ArrayLiteralParser();

        $this->expectException(ConversionError::class);
        $this->expectExceptionMessage('Unsupported PHP syntax');

        $parser->parse("[...'items']");
    }
}
